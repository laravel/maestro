<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;

dataset('users', [
    'verified user' => fn () => User::factory()->create(),
    'unverified user' => fn () => User::factory()->unverified()->create(),
]);

test('profile page is displayed', function (User $user) {
    actingAs($user);

    visit(route('profile.edit'))
        ->assertSee('Update your name and email address')
        ->assertValue('name', $user->name)
        ->assertValue('email', $user->email)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
})->with('users');

test('profile information can be updated', function () {
    actingAs($user = User::factory()->create());

    visit(route('profile.edit'))
        ->assertSee('Update your name and email address')
        ->fill('name', 'Test User')
        ->fill('email', 'test@example.com')
        ->press('@update-profile-button')
        ->assertSee('Saved')
        ->assertUrlIs(route('profile.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
})->with('users');;

test('email verification status is unchanged when the email address is unchanged', function () {
    actingAs($user = User::factory()->create());

    visit(route('profile.edit'))
        ->assertSee('Update your name and email address')
        ->fill('name', 'Test User')
        ->fill('email', $user->email)
        ->press('@update-profile-button')
        ->assertSee('Saved')
        ->assertUrlIs(route('profile.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    actingAs($user = User::factory()->create());

    visit(route('profile.edit'))
        ->press('@delete-user-button')
        ->assertSee('Are you sure you want to delete your account?')
        ->fill('password', 'password')
        ->press('@confirm-delete-user-button')
        ->assertUrlIs(route('home'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    actingAs($user = User::factory()->create());

    visit(route('profile.edit'))
        ->press('@delete-user-button')
        ->assertSee('Are you sure you want to delete your account?')
        ->fill('password', 'wrong-password')
        ->press('@confirm-delete-user-button')
        ->assertUrlIs(route('profile.edit'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    assertAuthenticated();
    expect($user->fresh())->not->toBeNull();
});
