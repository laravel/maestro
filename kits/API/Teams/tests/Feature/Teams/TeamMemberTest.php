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

    public function test_team_member_roles_can_be_updated_by_owners(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->patchJson(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'members')
            ->assertJsonPath('data.attributes.role', TeamRole::Admin->value);

        $this->assertEquals(
            TeamRole::Admin->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_team_member_roles_cannot_be_updated_by_non_owners(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->withToken($admin->createToken('auth')->plainTextToken)
            ->patchJson(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();

        $this->assertEquals(
            TeamRole::Member->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_team_owner_cannot_change_their_own_role(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->patchJson(route('teams.members.update', [$team, $owner]), [
                'role' => TeamRole::Admin->value,
            ]);

        $response->assertForbidden();

        $this->assertEquals(
            TeamRole::Owner->value,
            $team->members()->where('user_id', $owner->id)->first()->pivot->role->value,
        );
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_team_members_can_be_removed_by_owners(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->deleteJson(route('teams.members.destroy', [$team, $member]));

        $response->assertNoContent();

        $this->assertFalse($member->fresh()->belongsToTeam($team));
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_team_members_cannot_be_removed_by_non_owners(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->withToken($admin->createToken('auth')->plainTextToken)
            ->deleteJson(route('teams.members.destroy', [$team, $member]));

        $response->assertForbidden();

        $this->assertTrue($member->fresh()->belongsToTeam($team));
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_team_owner_cannot_be_removed(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->deleteJson(route('teams.members.destroy', [$team, $owner]));

        $response->assertForbidden();

        $this->assertTrue($owner->fresh()->belongsToTeam($team));
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }

    public function test_removing_a_user_who_is_not_a_team_member_returns_404(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $membershipsBefore = $team->memberships()->count();

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->deleteJson(route('teams.members.destroy', [$team, $stranger]));

        $response->assertNotFound();

        $this->assertSame($membershipsBefore, $team->fresh()->memberships()->count());
        $this->assertFalse($stranger->fresh()->belongsToTeam($team));
    }

    public function test_team_member_role_cannot_be_set_to_owner(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Member->value]);

        $response = $this
            ->withToken($owner->createToken('auth')->plainTextToken)
            ->patchJson(route('teams.members.update', [$team, $member]), [
                'role' => TeamRole::Owner->value,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertEquals(
            TeamRole::Member->value,
            $team->members()->where('user_id', $member->id)->first()->pivot->role->value,
        );
        $this->assertTrue($team->fresh()->owner()->is($owner));
    }
}
