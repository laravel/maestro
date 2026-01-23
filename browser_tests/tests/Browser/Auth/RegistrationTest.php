<?php

use App\Models\User;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

test('registration screen can be rendered', function () {
    visit(route('register'))
        ->assertSee('Create an account')
        ->assertSee('Enter your details below to create your account')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('new user can be registered', function () {
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
});

test('new user cannot be registered when email has already been taken', function () {
    User::factory()->create([
        'name' => 'Taylor',
        'email' => 'taylor@laravel.com',
    ]);

    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'password')
        ->press('@register-user-button')
        ->assertSee('The email has already been taken.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertGuest();
});

test('new user cannot be registered when password does not match', function () {
    visit(route('register'))
        ->fill('name', 'Taylor Otwell')
        ->fill('email', 'taylor@laravel.com')
        ->fill('password', 'password')
        ->fill('password_confirmation', 'secret')
        ->press('@register-user-button')
        ->assertSee('The password field confirmation does not match.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertGuest();
});
