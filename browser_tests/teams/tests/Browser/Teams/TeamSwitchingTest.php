<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

test('user can switch teams from the team switcher', function () {
    $user = User::factory()->create();
    $secondTeam = Team::factory()->create(['name' => 'Second Team']);
    $secondTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    actingAs($user);

    visit(route('dashboard'))
        ->click('@team-switcher-trigger')
        ->assertSee('Second Team')
        ->click('Second Team')
        ->assertPathContains($secondTeam->slug)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('switching team updates team-scoped url', function () {
    $user = User::factory()->create();
    $secondTeam = Team::factory()->create(['name' => 'Second Team']);
    $secondTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    actingAs($user);

    $personalTeam = $user->personalTeam();

    visit(route('teams.edit', $personalTeam))
        ->assertPathContains($personalTeam->slug)
        ->click('@team-switcher-trigger')
        ->click('Second Team')
        ->assertPathContains($secondTeam->slug)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('navigation works after team switch', function () {
    $user = User::factory()->create();
    $secondTeam = Team::factory()->create(['name' => 'Second Team']);
    $secondTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    actingAs($user);

    visit(route('dashboard'))
        ->click('@team-switcher-trigger')
        ->click('Second Team')
        ->assertPathContains($secondTeam->slug);

    visit(route('teams.index'))
        ->assertSee('Teams')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
