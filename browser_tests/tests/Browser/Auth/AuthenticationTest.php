<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

test('login screen can be rendered', function () {
    visit(route('login'))
        ->assertSee('Log in to your account')
        ->assertSee('Enter your email and password below to log in')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    visit(route('login'))
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('@login-button')
        ->assertUrlIs(route('dashboard'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    visit(route('login'))
        ->fill('email', $user->email)
        ->fill('password', 'wrong-password')
        ->press('@login-button')
        ->assertUrlIs(route('login'))
        ->assertSee('These credentials do not match our records.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    actingAs($user);

    visit(route('dashboard'))
        ->click('@sidebar-menu-button')
        ->click('@logout-button')
        ->assertUrlIs(route('home'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertGuest();
});
