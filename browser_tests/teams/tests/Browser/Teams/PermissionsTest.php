<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('admin can access team page but not see owner-only controls', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Admin View Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $admin = User::factory()->create(['name' => 'Admin User']);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->switchTeam($team);

    actingAs($admin);

    visit(route('teams.edit', $team))
        ->assertSee('Admin View Team')
        ->assertSee('Team members')
        ->assertVisible('@invite-member-button')
        ->assertMissing('@delete-team-button')
        ->assertMissing('@member-remove-button')
        ->assertMissing('@member-role-trigger')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('member can access team page in read-only mode', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Member View Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $member = User::factory()->create(['name' => 'Regular Member']);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    actingAs($member);

    visit(route('teams.edit', $team))
        ->assertSee('Member View Team')
        ->assertSee('Team members')
        ->assertMissing('@team-name-input')
        ->assertMissing('@team-save-button')
        ->assertMissing('@invite-member-button')
        ->assertMissing('@delete-team-button')
        ->assertMissing('@member-remove-button')
        ->assertMissing('@member-role-trigger')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('admin can update team name', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Admin Edit Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $admin = User::factory()->create(['name' => 'Admin Editor']);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->switchTeam($team);

    actingAs($admin);

    visit(route('teams.edit', $team))
        ->assertVisible('@team-name-input')
        ->assertVisible('@team-save-button')
        ->clear('@team-name-input')
        ->fill('@team-name-input', 'Admin Renamed')
        ->pressAndWaitFor('@team-save-button')
        ->assertSee('Team updated.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Team::where('name', 'Admin Renamed')->exists())->toBeTrue();
});
