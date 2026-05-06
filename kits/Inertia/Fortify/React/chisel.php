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

function formatFiles(Chisel $c): void
{
    if (runCommand(['composer', 'lint']) !== 0) {
        exit(1);
    }

    if (runCommand(['php', 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction']) !== 0) {
        exit(1);
    }

    $c->npm()->run('lint');
    $c->npm()->run('format');
}

function restoreAlphabetizedImportOrdering(Chisel $c): void
{
    $c->file('eslint.config.js')
        ->replace('// alphabetize: {', 'alphabetize: {')
        ->replace("//     order: 'asc',", "    order: 'asc',")
        ->replace('//     caseInsensitive: true,', '    caseInsensitive: true,')
        ->replace('// },', '},');
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
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'resources/js/pages/auth/login.tsx',
                'resources/js/pages/welcome.tsx',
            )->removeSectionMarkers('chisel-registration');
        },
        else: function (Chisel $c) {
            $c->file('config/fortify.php')->removeSection('chisel-registration');

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'resources/js/pages/auth/login.tsx',
                'resources/js/pages/welcome.tsx',
            )->removeSection('chisel-registration');

            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Http/Responses/RegisterResponse.php',
                'resources/js/pages/auth/register.tsx',
                'tests/Feature/Auth/RegistrationTest.php',
            )->delete();
        },
    )
    ->selected('auth_features', 'email-verification',
        then: function (Chisel $c) {
            $c->files(
                'config/fortify.php',
                'resources/js/pages/settings/profile.tsx',
                'app/Providers/FortifyServiceProvider.php',
            )->removeSectionMarkers('chisel-email-verification');
        },
        else: function (Chisel $c) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'resources/js/pages/settings/profile.tsx',
            )->removeSection('chisel-email-verification');

            $c->files(
                'resources/js/pages/auth/verify-email.tsx',
                'tests/Feature/Auth/EmailVerificationTest.php',
                'tests/Feature/Auth/VerificationNotificationTest.php',
            )->delete();
        },
    )
    ->selected('auth_features', '2fa',
        then: function (Chisel $c) {
            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                'resources/js/pages/settings/security.tsx',
                'resources/js/types/auth.ts',
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
            )->removeSectionMarkers('chisel-2fa');
        },
        else: function (Chisel $c) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Laravel\Fortify\TwoFactorAuthenticatable')
                ->removeTrait('TwoFactorAuthenticatable');

            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'resources/js/pages/settings/security.tsx',
                'resources/js/types/auth.ts',
            )->removeSection('chisel-2fa');

            $c->npm()->remove('input-otp');

            $c->files(
                'resources/js/pages/auth/two-factor-challenge.tsx',
                'resources/js/components/two-factor-setup-modal.tsx',
                'resources/js/components/two-factor-recovery-codes.tsx',
                'resources/js/components/ui/input-otp.tsx',
                'resources/js/hooks/use-two-factor-auth.ts',
                'resources/js/components/manage-two-factor.tsx',
                'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
                'tests/Feature/Auth/TwoFactorChallengeTest.php',
            )->delete();
        },
    )
    ->selected('auth_features', 'passkeys',
        then: function (Chisel $c) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Settings/SecurityTest.php',
                'resources/js/pages/settings/security.tsx',
                'resources/js/pages/auth/login.tsx',
                'resources/js/pages/auth/confirm-password.tsx',
            )->removeSectionMarkers('chisel-passkeys');
        },
        else: function (Chisel $c) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Laravel\Fortify\PasskeyAuthenticatable')
                ->removeImport('Laravel\Fortify\Contracts\PasskeyUser')
                ->removeTrait('PasskeyAuthenticatable')
                ->removeInterface('PasskeyUser');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Settings/SecurityTest.php',
                'resources/js/pages/settings/security.tsx',
                'resources/js/pages/auth/login.tsx',
                'resources/js/pages/auth/confirm-password.tsx',
            )->removeSection('chisel-passkeys');

            $c->npm()->remove('@laravel/passkeys');

            $c->files(
                'resources/js/components/passkey-item.tsx',
                'resources/js/components/passkey-register.tsx',
                'resources/js/components/passkey-verify.tsx',
                'resources/js/components/manage-passkeys.tsx',
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            )->delete();
        },
    )
    ->selectedAny('auth_features', ['2fa', 'passkeys'],
        then: function (Chisel $c) {
            $c->file('resources/js/pages/settings/security.tsx')
                ->removeSectionMarkers('chisel-2fa-or-passkeys');
        },
        else: function (Chisel $c) {
            $c->file('resources/js/pages/settings/security.tsx')
                ->removeSection('chisel-2fa-or-passkeys');
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
                'resources/js/pages/auth/confirm-password.tsx',
                'tests/Feature/Auth/PasswordConfirmationTest.php',
            )->delete();
        },
    )
    ->apply(function (Chisel $c): void {
        restoreAlphabetizedImportOrdering($c);
        formatFiles($c);
        cleanupInstallFeaturesArtifacts($c);
    });
