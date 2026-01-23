<?php

use App\Models\User;
use Composer\InstalledVersions;

use function Pest\Laravel\actingAs;

test('confirm password screen can be rendered', function () {
    actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->assertSee('Confirm your password')
        ->assertSee('This is a secure area of the application. Please confirm your password before continuing.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('password can be confirmed', function () {
    actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->fill('password', 'password')
        ->press('@confirm-password-button')
        ->assertUrlIs(route('dashboard'))
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('password is not confirmed with invalid password', function () {
    actingAs(User::factory()->create());

    // @TODO: The following check is only required to handle starter-kit without 2 factor authentication.
    $usesTwoFactorAuthentication = InstalledVersions::isInstalled('laravel/fortify');

    visit(route('password.confirm'))
        ->fill('password', 'wrong-password')
        ->press('@confirm-password-button')
        ->assertUrlIs(route('password.confirm'))
        ->assertSee($usesTwoFactorAuthentication ? 'The provided password was incorrect.' : 'The provided password is incorrect.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
