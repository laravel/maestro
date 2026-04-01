<?php

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
