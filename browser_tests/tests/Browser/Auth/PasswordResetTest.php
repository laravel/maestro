<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Laravel\post;

test('reset password link screen can be rendered', function () {
    visit(route('password.request'))
        ->assertSee('Forgot password')
        ->assertSee('Enter your email to receive a password reset link')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('test reset password link can be requested', function () {
    $user = User::factory()->create();

    Notification::fake();

    visit(route('password.request'))
        ->fill('email', $user->email)
        ->press('@email-password-reset-link-button')
        ->assertSee('We have emailed your password reset link.')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    $user = User::factory()->create();

    Notification::fake();

    Password::sendResetLink(['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        visit(route('password.reset', $notification->token))
            ->assertNoConsoleLogs()
            ->assertNoJavaScriptErrors();

        return true;
    });
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create();

    Notification::fake();

    Password::sendResetLink(['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        visit(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->fill('password', 'password')
            ->fill('password_confirmation', 'password')
            ->assertValue('email', $user->email)
            ->press('@reset-password-button')
            ->assertUrlIs(route('login'))
            ->assertSee('Your password has been reset.');

        return true;
    });
});
