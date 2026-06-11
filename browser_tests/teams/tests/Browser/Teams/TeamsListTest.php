<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('authenticated users can visit teams list', function () {
    actingAs(User::factory()->create());

    visit(route('teams.index'))
        ->assertSee('Teams')
        ->assertSee('Manage your teams and team memberships')
        ->assertVisible('@teams-new-team-button')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('personal team is listed on teams list', function () {
    $user = User::factory()->create();

    actingAs($user);

    visit(route('teams.index'))
        ->assertSee($user->personalTeam()->name)
        ->assertSee('Personal')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('new team can be created from teams list', function () {
    $user = User::factory()->create();

    actingAs($user);

    visit(route('teams.index'))
        ->click('@teams-new-team-button')
        ->waitForText('Create a new team')
        ->fill('@create-team-name', 'Test Team')
        ->pressAndWaitFor('@create-team-submit')
        ->assertPathContains('/settings/teams/')
        ->assertSee('Test Team')
        ->assertSee('Team created.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect(Team::where('name', 'Test Team')->exists())->toBeTrue();
});

test('new team appears in team switcher after creation', function () {
    $user = User::factory()->create();

    actingAs($user);

    visit(route('teams.index'))
        ->click('@teams-new-team-button')
        ->waitForText('Create a new team')
        ->fill('@create-team-name', 'New Switcher Team')
        ->pressAndWaitFor('@create-team-submit')
        ->assertPathContains('/settings/teams/')
        ->assertSee('Team created.')
        ->click('@team-switcher-trigger')
        ->assertSee('New Switcher Team')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('members can leave a non-personal team from teams list', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Leave Browser Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    actingAs($member);

    visit(route('teams.index'))
        ->assertSee('Leave Browser Team')
        ->assertVisible('@team-leave-button')
        ->click('@team-leave-button')
        ->waitForText('Leave team')
        ->assertVisible('@leave-team-confirm')
        ->pressAndWaitFor('@leave-team-confirm')
        ->assertPathEndsWith('/settings/teams')
        ->waitForText('You left the team "Leave Browser Team"')
        ->assertMissing('@team-leave-button')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($member->fresh()->belongsToTeam($team))->toBeFalse();
});

test('leave action is not available for personal or owned teams', function () {
    $user = User::factory()->create();
    $ownedTeam = Team::factory()->create(['name' => 'Owned Browser Team']);

    $ownedTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    actingAs($user);

    visit(route('teams.index'))
        ->assertSee($user->personalTeam()->name)
        ->assertSee('Owned Browser Team')
        ->assertMissing('@team-leave-button')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
