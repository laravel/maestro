<?php

use App\Models\User;

use function Pest\Laravel\assertAuthenticated;

test('new user registration creates a personal team', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertPathEndsWith('/dashboard')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertAuthenticated();

    $user = User::where('email', 'taylor@laravel.com')->first();
    $personalTeam = $user->personalTeam();

    expect($personalTeam)->not->toBeNull()
        ->and($personalTeam->is_personal)->toBeTrue()
        ->and($user->currentTeam->id)->toBe($personalTeam->id);
});

test('authenticated redirect lands on team-scoped dashboard', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertPathEndsWith('/dashboard')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $user = User::where('email', 'taylor@laravel.com')->first();
    $personalTeam = $user->personalTeam();

    expect($personalTeam)->not->toBeNull();
});

test('team switcher renders personal team after registration', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertPathEndsWith('/dashboard')
        ->click('@team-switcher-trigger')
        ->assertVisible('@team-switcher-item')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
