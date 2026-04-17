<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_team_invitations_can_be_accepted(): void
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

        $response = $this
            ->actingAs($invitedUser)
            ->postJson(route('invitations.accept', $invitation));

        $response->assertNoContent();

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_team_invitations_cannot_be_accepted_by_uninvited_user(): void
    {
        $owner = User::factory()->create();
        $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this
            ->actingAs($uninvitedUser)
            ->postJson(route('invitations.accept', $invitation))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invitation');

        $this->assertFalse($uninvitedUser->fresh()->belongsToTeam($team));
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

        $this
            ->actingAs($invitedUser)
            ->postJson(route('invitations.accept', $invitation))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invitation');

        $this->assertFalse($invitedUser->fresh()->belongsToTeam($team));
    }

    public function test_already_accepted_invitations_cannot_be_accepted_again(): void
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this
            ->actingAs($invitedUser)
            ->postJson(route('invitations.accept', $invitation))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('invitation');
    }
}
