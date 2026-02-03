<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_teams_index_page_can_be_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('teams.index'));

        $response->assertOk();
    }

    public function test_team_can_be_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::teams.index')
            ->set('name', 'Test Team')
            ->call('createTeam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'is_personal' => false,
        ]);
    }

    public function test_team_edit_page_can_be_displayed(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->get(route('teams.edit', $team));

        $response->assertOk();
    }

    public function test_team_can_be_updated_by_owner(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Original Name']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('teamName', 'Updated Name')
            ->call('updateTeam')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_team_cannot_be_updated_by_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($member);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('teamName', 'Updated Name')
            ->call('updateTeam')
            ->assertForbidden();
    }

    public function test_team_can_be_deleted_by_owner(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('deleteName', $team->name)
            ->call('deleteTeam')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_team_deletion_requires_name_confirmation(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('deleteName', 'Wrong Name')
            ->call('deleteTeam')
            ->assertHasErrors(['deleteName']);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_current_team_requires_new_team_selection(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->update(['current_team_id' => $team->id]);

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('deleteName', $team->name)
            ->call('deleteTeam')
            ->assertHasErrors(['newCurrentTeamId']);

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_current_team_switches_to_selected_team(): void
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $user->update(['current_team_id' => $team->id]);

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('deleteName', $team->name)
            ->set('newCurrentTeamId', $personalTeam->id)
            ->call('deleteTeam')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);

        $this->assertEquals($personalTeam->id, $user->fresh()->current_team_id);
    }

    public function test_personal_team_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();

        $this->actingAs($user);

        Livewire::test('pages::teams.edit', ['team' => $personalTeam])
            ->set('deleteName', $personalTeam->name)
            ->call('deleteTeam')
            ->assertForbidden();

        $this->assertDatabaseHas('teams', [
            'id' => $personalTeam->id,
            'deleted_at' => null,
        ]);
    }

    public function test_team_cannot_be_deleted_by_non_owner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $this->actingAs($member);

        Livewire::test('pages::teams.edit', ['team' => $team])
            ->set('deleteName', $team->name)
            ->call('deleteTeam')
            ->assertForbidden();
    }

    public function test_user_can_switch_team(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Member->value]);

        $this->actingAs($user);

        Livewire::test('pages::teams.index')
            ->call('switchTeam', $team->slug)
            ->assertHasNoErrors();

        $this->assertEquals($team->id, $user->fresh()->current_team_id);
    }

    public function test_user_cannot_switch_to_team_they_dont_belong_to(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();

        $this->actingAs($user);

        Livewire::test('pages::teams.index')
            ->call('switchTeam', $team->slug)
            ->assertForbidden();
    }

    public function test_guests_cannot_access_teams(): void
    {
        $response = $this->get(route('teams.index'));

        $response->assertRedirect(route('login'));
    }
}
