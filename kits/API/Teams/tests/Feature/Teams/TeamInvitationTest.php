<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_invitations_can_be_created(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'team_invitations')
            ->assertJsonPath('data.attributes.email', 'invited@example.com')
            ->assertJsonPath('data.attributes.role', TeamRole::Member->value);

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);

        Notification::assertSentOnDemand(TeamInvitationNotification::class);
    }

    public function test_team_invitations_can_be_created_by_admins(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

        $this
            ->actingAs($admin)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ])
            ->assertCreated();
    }

    public function test_existing_team_members_cannot_be_invited(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this
            ->actingAs($owner)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'member@example.com',
                'role' => TeamRole::Member->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_duplicate_invitations_cannot_be_created(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_team_invitations_cannot_be_created_by_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this
            ->actingAs($member)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ])
            ->assertForbidden();
    }

    public function test_team_invitations_can_be_cancelled_by_owners(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $this
            ->actingAs($owner)
            ->deleteJson(route('teams.invitations.destroy', [$team, $invitation]))
            ->assertNoContent();

        $this->assertDatabaseMissing('team_invitations', ['id' => $invitation->id]);
    }

    public function test_invited_user_can_accept_via_signed_email_link(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $this
            ->actingAs($owner)
            ->postJson(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ])
            ->assertCreated();

        $invitation = TeamInvitation::query()->where('email', 'invited@example.com')->firstOrFail();

        $actionUrl = null;

        Notification::assertSentOnDemand(
            TeamInvitationNotification::class,
            function (TeamInvitationNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($invitation, &$actionUrl): bool {
                if ($notifiable->routes['mail'] !== $invitation->email) {
                    return false;
                }

                $actionUrl = $notification->toMail($notifiable)->actionUrl;

                return $actionUrl !== null;
            },
        );

        $this->assertNotNull($actionUrl);
        $this->assertStringContainsString(route('invitations.accept', $invitation, false), $actionUrl);
        $this->assertStringContainsString('signature=', $actionUrl);

        $this->getJson($actionUrl)
            ->assertOk()
            ->assertJson(['message' => 'Invitation accepted successfully.']);

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_invited_user_can_accept_via_signed_route_directly(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Member,
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $this->getJson($signedUrl)
            ->assertOk()
            ->assertJson(['message' => 'Invitation accepted successfully.']);

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_accept_matches_user_email_case_insensitively(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'Invited@Example.COM',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $this->getJson($signedUrl)->assertOk();

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
    }

    public function test_accept_returns_403_when_signature_is_missing(): void
    {
        $owner = User::factory()->create();
        User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this->getJson(route('invitations.accept', $invitation))
            ->assertForbidden();

        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_accept_returns_403_when_signature_is_tampered(): void
    {
        $owner = User::factory()->create();
        User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $tamperedUrl = preg_replace('/signature=[^&]+/', 'signature=invalid-signature', $signedUrl);

        $this->getJson($tamperedUrl)->assertForbidden();
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_accept_returns_403_when_signature_is_expired(): void
    {
        $owner = User::factory()->create();
        User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->subMinute(),
            $invitation,
        );

        $this->getJson($signedUrl)->assertForbidden();
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_accept_returns_403_when_no_user_exists_for_invitation_email(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'noone@example.com',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $this->getJson($signedUrl)->assertForbidden();
        $this->assertNull($invitation->fresh()->accepted_at);
    }

    public function test_already_accepted_invitations_cannot_be_accepted_again(): void
    {
        $owner = User::factory()->create();
        User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $this->getJson($signedUrl)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invitation');
    }

    public function test_expired_invitations_cannot_be_accepted(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'invitations.accept',
            now()->addDays(3),
            $invitation,
        );

        $this->getJson($signedUrl)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invitation');

        $this->assertFalse($invitedUser->fresh()->belongsToTeam($team));
    }
}
