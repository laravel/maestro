<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER') ?: __DIR__.'/vendor/autoload.php';

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

function runCommand(array $command): int
{
    $escaped = array_map('escapeshellarg', $command);

    passthru(implode(' ', $escaped), $exitCode);

    return $exitCode;
}

function formatFiles(): void
{
    if (runCommand(['composer', 'lint']) !== 0) {
        exit(1);
    }
}

function existingFiles(string ...$paths): array
{
    return array_values(array_filter($paths, fn (string $path): bool => file_exists(__DIR__.'/'.$path)));
}

function cleanupInstallFeaturesArtifacts(Chisel $c): void
{
    $c->file('composer.json')
        ->removeLinesContaining('"@php artisan install:features --ansi",');

    $c->files(
        'app/Console/Commands/InstallFeaturesCommand.php',
        'chisel.php',
    )->delete();
}

return Chisel::script(__DIR__)
    ->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                'registration' => 'Registration',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
                'password-confirmation' => 'Password confirmation',
            ],
            default: ['email-verification', 'registration', '2fa', 'passkeys', 'password-confirmation'],
            hint: 'Use space to select, enter to confirm.',
        ),
    ])
    ->selected('auth_features', 'registration',
        then: function (Chisel $c) {
            $files = array_merge([
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'resources/views/pages/auth/login.blade.php',
                'resources/views/welcome.blade.php',
            ], existingFiles(
                'resources/views/livewire/auth/login.blade.php',
            ));

            $c->files(...$files)->removeSectionMarkers('chisel-registration');
        },
        else: function (Chisel $c) {
            $c->file('config/fortify.php')->removeSection('chisel-registration');

            $files = array_merge([
                'app/Providers/FortifyServiceProvider.php',
                'resources/views/pages/auth/login.blade.php',
                'resources/views/welcome.blade.php',
            ], existingFiles(
                'resources/views/livewire/auth/login.blade.php',
            ));

            $c->files(...$files)->removeSection('chisel-registration');

            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Http/Responses/RegisterResponse.php',
                'resources/views/pages/auth/register.blade.php',
                'resources/views/livewire/auth/register.blade.php',
                'tests/Feature/Auth/RegistrationTest.php',
            )->delete();
        },
    )
    ->selected('auth_features', 'email-verification',
        then: function (Chisel $c) {
            $files = existingFiles(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Livewire/Settings/Profile.php',
                'resources/views/pages/settings/profile.blade.php',
                'resources/views/livewire/settings/profile.blade.php',
            );

            if ($files !== []) {
                $c->files(...$files)->removeSectionMarkers('chisel-email-verification');
            }
        },
        else: function (Chisel $c) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $files = existingFiles(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Livewire/Settings/Profile.php',
                'resources/views/pages/settings/profile.blade.php',
                'resources/views/livewire/settings/profile.blade.php',
            );

            if ($files !== []) {
                $c->files(...$files)->removeSection('chisel-email-verification');
            }

            $c->files(
                'resources/views/pages/auth/verify-email.blade.php',
                'resources/views/livewire/auth/verify-email.blade.php',
                'tests/Feature/Auth/EmailVerificationTest.php',
            )->delete();
        },
    )
    ->selected('auth_features', '2fa',
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

            $c->files(...$files)->removeSectionMarkers('chisel-2fa');
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

            $c->files(...$files)->removeSection('chisel-2fa');

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
    )
    ->selected('auth_features', 'passkeys',
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

            $c->files(...$files)->removeSectionMarkers('chisel-passkeys');
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

            $c->files(...$files)->removeSection('chisel-passkeys');

            $c->npm()->remove('@laravel/passkeys');

            $c->files(
                'resources/views/components/passkey-verify.blade.php',
                'resources/views/components/passkey-registration.blade.php',
                'resources/js/passkeys.js',
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            )->delete();
        },
    )
    ->selected('auth_features', 'password-confirmation',
        then: function (Chisel $c) {
            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSectionMarkers('chisel-password-confirmation');
        },
        else: function (Chisel $c) {
            $c->file('config/fortify.php')
                ->replace("'confirmPassword' => true,", "'confirmPassword' => false,");

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSection('chisel-password-confirmation');

            $c->files(
                'resources/views/pages/auth/confirm-password.blade.php',
                'resources/views/livewire/auth/confirm-password.blade.php',
                'tests/Feature/Auth/PasswordConfirmationTest.php',
            )->delete();
        },
    )
    ->apply(function (Chisel $c): void {
        formatFiles();
        cleanupInstallFeaturesArtifacts($c);
    });
