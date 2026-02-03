<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_invitation_can_be_created(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $this->actingAs($owner);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('inviteEmail', 'invited@example.com')
            ->set('inviteRole', TeamRole::Member->value)
            ->call('createInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);
    }

    public function test_team_invitation_can_be_created_by_admin(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

        $this->actingAs($admin);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('inviteEmail', 'invited@example.com')
            ->set('inviteRole', TeamRole::Member->value)
            ->call('createInvitation')
            ->assertHasNoErrors();
    }

    public function test_team_invitation_cannot_be_created_by_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($member);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('inviteEmail', 'invited@example.com')
            ->set('inviteRole', TeamRole::Member->value)
            ->call('createInvitation')
            ->assertForbidden();
    }

    public function test_team_invitation_can_be_cancelled_by_owner(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $this->actingAs($owner);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->call('cancelInvitation', $invitation->code)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_team_invitation_can_be_accepted(): void
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

        $this->actingAs($invitedUser);

        $response = Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation]);

        $response->assertRedirect(route('dashboard'));

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_team_invitation_cannot_be_accepted_by_wrong_user(): void
    {
        $owner = User::factory()->create();
        $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $this->actingAs($wrongUser);

        $response = Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation]);

        $response->assertHasErrors(['invitation']);

        $this->assertFalse($wrongUser->fresh()->belongsToTeam($team));
    }

    public function test_expired_invitation_cannot_be_accepted(): void
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

        $this->actingAs($invitedUser);

        $response = Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation]);

        $response->assertHasErrors(['invitation']);

        $this->assertFalse($invitedUser->fresh()->belongsToTeam($team));
    }

    public function test_already_accepted_invitation_cannot_be_accepted_again(): void
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

        $this->actingAs($invitedUser);

        $response = Livewire::test('pages::teams.accept-invitation', ['invitation' => $invitation]);

        $response->assertHasErrors(['invitation']);
    }
}
