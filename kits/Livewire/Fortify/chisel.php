<?php

require getenv('LARAVEL_INSTALLER_AUTOLOADER') ?: __DIR__.'/vendor/autoload.php';

use Laravel\Chisel\Chisel;
use Laravel\Chisel\Question;

/**
 * Variant-specific filenames are supplied by the sibling chisel-paths.php.
 * The Single-File Component variant ships the default paths file; the
 * Multi-File Component variant overlays its own copy during build.
 *
 * @var array{
 *     welcome: string,
 *     login: string,
 *     register: string,
 *     confirm_password: string,
 *     verify_email: string,
 *     two_factor_challenge: string,
 *     profile_files: list<string>,
 *     security_files: list<string>,
 *     two_factor_files: list<string>,
 * } $paths
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
                'app/Providers/FortifyServiceProvider.php',
                ...$paths['profile_files'],
            )->removeSectionMarkers('chisel-email-verification');
        },
        else: function (Chisel $c) use ($paths) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $c->files(
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                ...$paths['profile_files'],
            )->removeSection('chisel-email-verification');

            $c->files(
                $paths['verify_email'],
                'tests/Feature/Auth/EmailVerificationTest.php',
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
                'config/fortify.php',
                'app/Providers/FortifyServiceProvider.php',
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
                ...$paths['security_files'],
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
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
                ...$paths['security_files'],
            )->removeSection('chisel-2fa');

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
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
                'vite.config.js',
                $paths['login'],
                $paths['confirm_password'],
                ...$paths['security_files'],
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
                'routes/settings.php',
                'tests/Feature/Settings/SecurityTest.php',
                'vite.config.js',
                $paths['login'],
                $paths['confirm_password'],
                ...$paths['security_files'],
            )->removeSection('chisel-passkeys');

            $c->npm()->remove('@laravel/passkeys');

            $c->files(
                'resources/views/components/passkey-verify.blade.php',
                'resources/views/components/passkey-registration.blade.php',
                'resources/js/passkeys.js',
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            )->delete();
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
        chiselRun(['composer', 'lint'], 'Composer Lint');

        $c->file('composer.json')
            ->removeLinesContaining('"@php artisan install:features --ansi"');

        $c->files(
            'app/Console/Commands/InstallFeaturesCommand.php',
            'chisel.php',
            'chisel-paths.php',
        )->delete();
    });

function chiselRun(array $command, string $label): void
{
    $escaped = array_map('escapeshellarg', $command);
    passthru(implode(' ', $escaped), $exitCode);

    if ($exitCode === 0) {
        return;
    }

    fwrite(
        STDERR,
        "\nchisel: {$label} step failed (exit {$exitCode}). Your project may be in a partially-modified state — review the output above before continuing.\n",
    );

    exit($exitCode);
}
