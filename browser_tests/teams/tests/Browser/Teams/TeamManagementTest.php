<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('owner can rename a non-personal team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Original Name']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->assertVisible('@team-name-input')
        ->clear('@team-name-input')
        ->fill('@team-name-input', 'Updated Name')
        ->pressAndWaitFor('@team-save-button')
        ->assertSee('Team updated.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Team::where('name', 'Updated Name')->exists())->toBeTrue();
});

test('delete flow requires typing the team name', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Delete Me']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->click('@delete-team-button')
        ->waitForText('Are you sure?')
        ->assertVisible('@delete-team-name')
        ->assertVisible('@delete-team-confirm')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('deleting a non-personal team redirects to teams index', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Team To Delete']);
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($team);

    actingAs($user);

    visit(route('teams.edit', $team))
        ->click('@delete-team-button')
        ->waitForText('Are you sure?')
        ->fill('@delete-team-name', 'Team To Delete')
        ->pressAndWaitFor('@delete-team-confirm')
        ->assertPathEndsWith('/settings/teams')
        ->assertSee('Teams')
        ->assertSee('Team deleted.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Team::where('name', 'Team To Delete')->whereNull('deleted_at')->exists())->toBeFalse();
});

test('delete action is not available for personal teams', function () {
    $user = User::factory()->create();

    actingAs($user);

    $personalTeam = $user->personalTeam();

    visit(route('teams.edit', $personalTeam))
        ->assertMissing('@delete-team-button')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
