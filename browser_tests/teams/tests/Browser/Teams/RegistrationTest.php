<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\assertAuthenticated;

test('new user registration creates a personal team', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertPathEndsWith('/email/verify')
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
        ->assertPathEndsWith('/email/verify')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $user = User::where('email', 'taylor@laravel.com')->first();
    $personalTeam = $user->personalTeam();

    expect($personalTeam)->not->toBeNull();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    visit($verificationUrl)
        ->assertPathEndsWith('/dashboard')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('team switcher renders personal team after registration', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertPathEndsWith('/email/verify');

    $user = User::where('email', 'taylor@laravel.com')->first();
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    visit($verificationUrl)
        ->click('@team-switcher-trigger')
        ->assertVisible('@team-switcher-item')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
