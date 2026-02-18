<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_member_role_can_be_updated_by_owner()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->patch(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertEquals(
            TeamRole::Admin->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
    }

    public function test_team_member_role_cannot_be_updated_by_non_owner()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();
    }

    public function test_team_member_can_be_removed_by_owner()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $response->assertRedirect(route('teams.edit', $team));

        $this->assertFalse($member->fresh()->belongsToTeam($team));
    }

    public function test_team_member_cannot_be_removed_by_non_owner()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($admin)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $response->assertForbidden();
    }

    public function test_removed_member_current_team_is_set_to_personal_team()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $personalTeam = $member->personalTeam();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $member->update(['current_team_id' => $team->id]);

        $this
            ->actingAs($owner)
            ->delete(route('teams.members.destroy', [$team, $member]));

        $this->assertEquals($personalTeam->id, $member->fresh()->current_team_id);
    }
}
