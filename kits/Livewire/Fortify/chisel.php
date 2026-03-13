<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER');

use Laravel\Chisel\Chisel;

function runCommand(array $command): int
{
    $escaped = array_map('escapeshellarg', $command);

    passthru(implode(' ', $escaped), $exitCode);

    return $exitCode;
}

function formatFiles(): void
{
    $pint = DIRECTORY_SEPARATOR === '\\'
        ? __DIR__.'/vendor/bin/pint.bat'
        : __DIR__.'/vendor/bin/pint';

    if (file_exists($pint) && runCommand([$pint]) !== 0) {
        exit(1);
    }
}

function existingFiles(string ...$paths): array
{
    return array_values(array_filter($paths, fn (string $path): bool => file_exists(__DIR__.'/'.$path)));
}

$c = Chisel::in(__DIR__)
    ->withAnswers($argv[1] ?? null)
    ->multiselect('auth_features', 'Which authentication features would you like to enable?', [
        'email-verification' => 'Email verification',
        '2fa' => 'Two-factor authentication',
        'passkeys' => 'Passkeys',
    ], hint: 'Use space to select, enter to confirm.');

$c->selected('auth_features', 'email-verification',
    then: function (Chisel $c) {
        $files = existingFiles(
            'app/Providers/FortifyServiceProvider.php',
            'app/Livewire/Settings/Profile.php',
            'resources/views/pages/settings/profile.blade.php',
            'resources/views/livewire/settings/profile.blade.php',
        );

        if ($files !== []) {
            $c->files(...$files)->removeSectionMarkers('email-verification');
        }
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
            ->removeInterface('MustVerifyEmail');

        $c->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');

        $files = existingFiles(
            'app/Providers/FortifyServiceProvider.php',
            'app/Livewire/Settings/Profile.php',
            'resources/views/pages/settings/profile.blade.php',
            'resources/views/livewire/settings/profile.blade.php',
        );

        if ($files !== []) {
            $c->files(...$files)->removeSection('email-verification');
        }

        $c->files(
            'resources/views/pages/auth/verify-email.blade.php',
            'resources/views/livewire/auth/verify-email.blade.php',
            'tests/Feature/Auth/EmailVerificationTest.php',
        )->delete();
    },
);

$c->selected('auth_features', '2fa',
    then: function (Chisel $c) {
        $files = array_merge([
            'app/Models/User.php',
            'database/factories/UserFactory.php',
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'routes/settings.php',
            'tests/Feature/Settings/SecurityTest.php',
        ], existingFiles(
            'app/Livewire/Settings/Security.php',
            'resources/views/pages/settings/security.blade.php',
            'resources/views/livewire/settings/security.blade.php',
        ));

        $c->files(...$files)->removeSectionMarkers('2fa');
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Laravel\Fortify\TwoFactorAuthenticatable')
            ->removeTrait('TwoFactorAuthenticatable');

        $files = array_merge([
            'app/Models/User.php',
            'database/factories/UserFactory.php',
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'routes/settings.php',
            'tests/Feature/Settings/SecurityTest.php',
        ], existingFiles(
            'app/Livewire/Settings/Security.php',
            'resources/views/pages/settings/security.blade.php',
            'resources/views/livewire/settings/security.blade.php',
        ));

        $c->files(...$files)->removeSection('2fa');

        $c->files(
            'resources/views/pages/auth/two-factor-challenge.blade.php',
            'resources/views/livewire/auth/two-factor-challenge.blade.php',
            'resources/views/pages/settings/two-factor/recovery-codes.blade.php',
            'resources/views/pages/settings/two-factor-setup-modal.blade.php',
            'resources/views/livewire/settings/two-factor/recovery-codes.blade.php',
            'app/Livewire/Settings/TwoFactor/RecoveryCodes.php',
            'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
            'tests/Feature/Auth/TwoFactorChallengeTest.php',
        )->delete();
    },
);

$c->selected('auth_features', 'passkeys',
    then: function (Chisel $c) {
        $files = array_merge([
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'routes/settings.php',
            'tests/Feature/Settings/SecurityTest.php',
            'vite.config.js',
        ], existingFiles(
            'app/Livewire/Settings/Security.php',
            'resources/views/pages/settings/security.blade.php',
            'resources/views/livewire/settings/security.blade.php',
            'resources/views/pages/auth/login.blade.php',
            'resources/views/pages/auth/confirm-password.blade.php',
            'resources/views/livewire/auth/login.blade.php',
            'resources/views/livewire/auth/confirm-password.blade.php',
        ));

        $c->files(...$files)->removeSectionMarkers('passkeys');
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Laravel\Fortify\PasskeyAuthenticatable')
            ->removeImport('Laravel\Fortify\Contracts\PasskeyUser')
            ->removeTrait('PasskeyAuthenticatable')
            ->removeInterface('PasskeyUser');

        $files = array_merge([
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'routes/settings.php',
            'tests/Feature/Settings/SecurityTest.php',
            'vite.config.js',
        ], existingFiles(
            'app/Livewire/Settings/Security.php',
            'resources/views/pages/settings/security.blade.php',
            'resources/views/livewire/settings/security.blade.php',
            'resources/views/pages/auth/login.blade.php',
            'resources/views/pages/auth/confirm-password.blade.php',
            'resources/views/livewire/auth/login.blade.php',
            'resources/views/livewire/auth/confirm-password.blade.php',
        ));

        $c->files(...$files)->removeSection('passkeys');

        $c->npm()->remove('@laravel/passkeys');

        $c->files(
            'resources/views/components/passkey-verify.blade.php',
            'resources/views/components/passkey-registration.blade.php',
            'resources/js/passkeys.js',
            'database/migrations/2024_01_01_000000_create_passkeys_table.php',
        )->delete();
    },
);

formatFiles();
