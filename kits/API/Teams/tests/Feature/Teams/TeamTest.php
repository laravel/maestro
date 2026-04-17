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

    public function test_user_can_list_their_teams(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->getJson(route('teams.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['type', 'id', 'attributes' => ['name', 'slug', 'is_personal']],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_team_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson(route('teams.store'), [
                'name' => 'Test Team',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'teams')
            ->assertJsonPath('data.attributes.name', 'Test Team')
            ->assertJsonPath('data.attributes.is_personal', false);

        $this->assertDatabaseHas('teams', [
            'name' => 'Test Team',
            'is_personal' => false,
        ]);
    }

    public function test_team_slug_uses_next_available_suffix(): void
    {
        $user = User::factory()->create();

        Team::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
        Team::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
        Team::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

        $this
            ->actingAs($user)
            ->postJson(route('teams.store'), ['name' => 'Acme'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.slug', 'acme-11');

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme',
            'slug' => 'acme-11',
        ]);
    }

    public function test_team_details_can_be_retrieved(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($owner)
            ->getJson(route('teams.show', $team));

        $response->assertOk()
            ->assertJsonPath('data.type', 'teams')
            ->assertJsonPath('data.attributes.name', $team->name)
            ->assertJsonStructure([
                'data' => ['type', 'id', 'attributes' => ['name', 'slug', 'is_personal']],
                'meta' => [
                    'members' => [['type', 'id', 'attributes' => ['name', 'email', 'role', 'role_label']]],
                    'invitations',
                    'permissions' => [
                        'canUpdateTeam',
                        'canDeleteTeam',
                        'canAddMember',
                        'canUpdateMember',
                        'canRemoveMember',
                        'canCreateInvitation',
                        'canCancelInvitation',
                    ],
                    'available_roles' => [['value', 'label']],
                ],
            ])
            ->assertJsonCount(2, 'meta.members');
    }

    public function test_team_can_be_updated_by_owners(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Original Name']);
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->patchJson(route('teams.update', $team), [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.name', 'Updated Name');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_team_cannot_be_updated_by_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($member)
            ->patchJson(route('teams.update', $team), ['name' => 'Updated Name']);

        $response->assertForbidden();
    }

    public function test_team_can_be_deleted_by_owners(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('teams.destroy', $team), ['name' => $team->name]);

        $response->assertNoContent();

        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    public function test_team_deletion_requires_name_confirmation(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('teams.destroy', $team), ['name' => 'Wrong Name']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'deleted_at' => null,
        ]);
    }

    public function test_personal_teams_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $personalTeam = $user->personalTeam();

        $response = $this
            ->actingAs($user)
            ->deleteJson(route('teams.destroy', $personalTeam), ['name' => $personalTeam->name]);

        $response->assertForbidden();

        $this->assertDatabaseHas('teams', [
            'id' => $personalTeam->id,
            'deleted_at' => null,
        ]);
    }

    public function test_teams_cannot_be_deleted_by_non_owners(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->actingAs($member)
            ->deleteJson(route('teams.destroy', $team), ['name' => $team->name]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_users_cannot_access_teams(): void
    {
        $this->getJson(route('teams.index'))->assertUnauthorized();
    }
}
