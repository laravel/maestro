<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_index_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('teams.index'));

        $response->assertOk();
    }

    public function test_team_can_be_created()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('teams.store'), [
                'name' => 'Test Team',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'is_personal' => false,
        ]);
    }

    public function test_team_slug_uses_next_available_suffix()
    {
        $user = User::factory()->create();

        Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
        Team::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

        $this
            ->actingAs($user)
            ->post(route('teams.store'), [
                'name' => 'Acme',
            ]);

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme',
            'slug' => 'acme-11',
        ]);
    }

    public function test_team_edit_page_can_be_rendered()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get(route('teams.edit', $team));

        $response->assertOk();
    }

    public function test_team_can_be_updated_by_owner()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Original Name']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->patch(route('teams.update', $team), [
                'name' => 'Updated Name',
            ]);

        $response->assertRedirect(route('teams.edit', $team->fresh()));

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_team_cannot_be_updated_by_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($member)
            ->patch(route('teams.update', $team), [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_team_can_be_deleted_by_owner()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->delete(route('teams.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_team_deletion_requires_name_confirmation()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->delete(route('teams.destroy', $team), [
                'name' => 'Wrong Name',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_current_team_requires_new_team_selection()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        // Set the team as the current team
        $user->update(['current_team_id' => $team->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('teams.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertSessionHasErrors('new_current_team_id');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_current_team_switches_to_selected_team()
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        // Set the team as the current team
        $user->update(['current_team_id' => $team->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('teams.destroy', $team), [
                'name' => $team->name,
                'new_current_team_id' => $personalTeam->id,
            ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);

        $this->assertEquals($personalTeam->id, $user->fresh()->current_team_id);
    }

    public function test_personal_team_cannot_be_deleted()
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();

        $response = $this
            ->actingAs($user)
            ->delete(route('teams.destroy', $personalTeam), [
                'name' => $personalTeam->name,
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('teams', [
            'id' => $personalTeam->id,
            'deleted_at' => null,
        ]);
    }

    public function test_team_cannot_be_deleted_by_non_owner()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($member)
            ->delete(route('teams.destroy', $team), [
                'name' => $team->name,
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_switch_team()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($user)
            ->post(route('teams.switch', $team));

        $response->assertRedirect();

        $this->assertEquals($team->id, $user->fresh()->current_team_id);
    }

    public function test_user_cannot_switch_to_team_they_dont_belong_to()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('teams.switch', $team));

        $response->assertForbidden();
    }

    public function test_guests_cannot_access_teams()
    {
        $response = $this->get(route('teams.index'));

        $response->assertRedirect(route('login'));
    }
}
