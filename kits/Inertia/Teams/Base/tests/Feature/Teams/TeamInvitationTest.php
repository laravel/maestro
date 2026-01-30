<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_invitation_can_be_created()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->post(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ]);

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Member->value,
        ]);
    }

    public function test_team_invitation_can_be_created_by_admin()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);

        $response = $this
            ->actingAs($admin)
            ->post(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ]);

        $response->assertRedirect(route('teams.edit', $team));
    }

    public function test_team_invitation_cannot_be_created_by_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($member)
            ->post(route('teams.invitations.store', $team), [
                'email' => 'invited@example.com',
                'role' => TeamRole::Member->value,
            ]);

        $response->assertForbidden();
    }

    public function test_team_invitation_can_be_cancelled_by_owner()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('teams.invitations.destroy', [$team, $invitation]));

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_team_invitation_can_be_accepted()
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
            ->get(route('invitations.accept', $invitation));

        $response->assertRedirect(route('dashboard', ['current_team' => $team->slug]));

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_team_invitation_cannot_be_accepted_by_wrong_user()
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

        $response = $this
            ->actingAs($wrongUser)
            ->get(route('invitations.accept', $invitation));

        $response->assertSessionHasErrors('invitation');

        $this->assertFalse($wrongUser->fresh()->belongsToTeam($team));
    }

    public function test_expired_invitation_cannot_be_accepted()
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

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('invitations.accept', $invitation));

        $response->assertSessionHasErrors('invitation');

        $this->assertFalse($invitedUser->fresh()->belongsToTeam($team));
    }

    public function test_already_accepted_invitation_cannot_be_accepted_again()
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

        $response = $this
            ->actingAs($invitedUser)
            ->get(route('invitations.accept', $invitation));

        $response->assertSessionHasErrors('invitation');
    }
}
