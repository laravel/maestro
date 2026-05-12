<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER') ?: __DIR__.'/vendor/autoload.php';

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

/**
 * Framework-specific filenames are supplied by the sibling chisel-paths.php
 * that ships with each Inertia kit (React/Svelte/Vue). After build both files
 * land in the project root.
 *
 * @var array{
 *     login: string,
 *     register: string,
 *     welcome: string,
 *     profile: string,
 *     security: string,
 *     verify_email: string,
 *     two_factor_challenge: string,
 *     confirm_password: string,
 *     auth_types: string,
 *     two_factor_files: list<string>,
 *     two_factor_otp_package: ?string,
 *     passkey_files: list<string>,
 *  } $paths
 */
$paths = require __DIR__.'/chisel-paths.php';

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
    ->selected(
        'auth_features',
        'registration',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                $paths['login'],
                $paths['welcome'],
            )->removeSectionMarkers('chisel-registration');
        },
        else: function (Chisel $c) use ($paths) {
            $c->file('config/fortify.php')->removeSection('chisel-registration');

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                $paths['login'],
                $paths['welcome'],
            )->removeSection('chisel-registration');

            $c->files(
                'app/Actions/Fortify/CreateNewUser.php',
                'app/Http/Responses/RegisterResponse.php',
                $paths['register'],
                'tests/Feature/Auth/RegistrationTest.php',
            )->delete();
        },
    )
    ->selected(
        'auth_features',
        'email-verification',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                $paths['profile'],
                'app/Providers/FortifyServiceProvider.php',
            )->removeSectionMarkers('chisel-email-verification');
        },
        else: function (Chisel $c) use ($paths) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                $paths['profile'],
            )->removeSection('chisel-email-verification');

            $c->files(
                $paths['verify_email'],
                'tests/Feature/Auth/EmailVerificationTest.php',
                'tests/Feature/Auth/VerificationNotificationTest.php',
            )->delete();
        },
    )
    ->selected(
        'auth_features',
        '2fa',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                $paths['security'],
                $paths['auth_types'],
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
            )->removeSectionMarkers('chisel-2fa');
        },
        else: function (Chisel $c) use ($paths) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Laravel\Fortify\TwoFactorAuthenticatable')
                ->removeTrait('TwoFactorAuthenticatable');

            $c->files(
                'app/Models/User.php',
                'database/factories/UserFactory.php',
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                $paths['security'],
                $paths['auth_types'],
            )->removeSection('chisel-2fa');

            if ($paths['two_factor_otp_package'] !== null) {
                $c->npm()->remove($paths['two_factor_otp_package']);
            }

            $c->files(...[
                $paths['two_factor_challenge'],
                ...$paths['two_factor_files'],
                'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
                'tests/Feature/Auth/TwoFactorChallengeTest.php',
            ])->delete();
        },
    )
    ->selected(
        'auth_features',
        'passkeys',
        then: function (Chisel $c) use ($paths) {
            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Settings/SecurityTest.php',
                $paths['security'],
                $paths['login'],
                $paths['confirm_password'],
            )->removeSectionMarkers('chisel-passkeys');
        },
        else: function (Chisel $c) use ($paths) {
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
                $paths['security'],
                $paths['login'],
                $paths['confirm_password'],
            )->removeSection('chisel-passkeys');

            $c->npm()->remove('@laravel/passkeys');

            $c->files(...[
                ...$paths['passkey_files'],
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            ])->delete();
        },
    )
    ->selectedAny(
        'auth_features',
        ['2fa', 'passkeys'],
        then: function (Chisel $c) use ($paths) {
            $c->file($paths['security'])
                ->removeSectionMarkers('chisel-2fa-or-passkeys');
        },
        else: function (Chisel $c) use ($paths) {
            $c->file($paths['security'])
                ->removeSection('chisel-2fa-or-passkeys');
        },
    )
    ->selected(
        'auth_features',
        'password-confirmation',
        then: function (Chisel $c) {
            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSectionMarkers('chisel-password-confirmation');
        },
        else: function (Chisel $c) use ($paths) {
            $c->file('config/fortify.php')
                ->replace("'confirmPassword' => true,", "'confirmPassword' => false,");

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSection('chisel-password-confirmation');

            $c->files(
                $paths['confirm_password'],
                'tests/Feature/Auth/PasswordConfirmationTest.php',
            )->delete();
        },
    )
    ->apply(function (Chisel $c): void {
        chiselRestoreAlphabetize($c);
        chiselFormatFiles($c);
        chiselCleanupInstallArtifacts($c);
    });

/**
 * Re-enable the import/order alphabetize rule that ships commented out so the
 * kit's chisel-marker'd imports lint clean in source.
 */
function chiselRestoreAlphabetize(Chisel $c): void
{
    $c->file('eslint.config.js')
        ->replace('// alphabetize: {', 'alphabetize: {')
        ->replace("//     order: 'asc',", "    order: 'asc',")
        ->replace('//     caseInsensitive: true,', '    caseInsensitive: true,')
        ->replace('// },', '},');
}

function chiselFormatFiles(Chisel $c): void
{
    if (chiselPassthru(['composer', 'lint']) !== 0) {
        exit(1);
    }

    if (chiselPassthru(['php', 'artisan', 'wayfinder:generate', '--with-form', '--no-interaction']) !== 0) {
        exit(1);
    }

    $c->npm()->run('lint');
    $c->npm()->run('format');
}

function chiselCleanupInstallArtifacts(Chisel $c): void
{
    $c->file('composer.json')
        ->removeLinesContaining('"@php artisan install:features --ansi",');

    $c->files(
        'app/Console/Commands/InstallFeaturesCommand.php',
        'chisel.php',
        'chisel-paths.php',
    )->delete();
}

function chiselPassthru(array $command): int
{
    $escaped = array_map('escapeshellarg', $command);

    passthru(implode(' ', $escaped), $exitCode);

    return $exitCode;
}
