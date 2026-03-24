<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    $requiresPasswordConfirmation = false;

    /* @chisel-2fa */
    $requiresPasswordConfirmation = $requiresPasswordConfirmation || (
        Features::canManageTwoFactorAuthentication()
        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
    );
    /* @end-chisel-2fa */
    /* @chisel-passkeys */
    $requiresPasswordConfirmation = $requiresPasswordConfirmation || (
        Features::canManagePasskeys()
        && Features::optionEnabled(Features::passkeys(), 'confirmPassword')
    );
    /* @end-chisel-passkeys */

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware(
            when($requiresPasswordConfirmation, ['password.confirm'], []),
        )
        ->name('security.edit');
});
