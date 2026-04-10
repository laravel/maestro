<?php

require (getenv('LARAVEL_INSTALLER_AUTOLOADER') ?: __DIR__.'/vendor/autoload.php');

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

    if (runCommand(['npm', 'run', 'lint']) !== 0) {
        exit(1);
    }

    if (runCommand(['npm', 'run', 'format']) !== 0) {
        exit(1);
    }
}

function restoreAlphabetizedImportOrdering(Chisel $c): void
{
    $c->file('eslint.config.js')
        ->replace('// alphabetize: {', 'alphabetize: {')
        ->replace("//     order: 'asc',", "    order: 'asc',")
        ->replace('//     caseInsensitive: true,', '    caseInsensitive: true,')
        ->replace('// },', '},');
}

return Chisel::script(__DIR__)
    ->questions([
        Question::multiselect(
            name: 'auth_features',
            label: 'Which authentication features would you like to enable?',
            options: [
                'email-verification' => 'Email verification',
                '2fa' => 'Two-factor authentication',
                'passkeys' => 'Passkeys',
                'password-confirmation' => 'Password confirmation',
            ],
            default: ['email-verification', '2fa', 'passkeys', 'password-confirmation'],
            hint: 'Use space to select, enter to confirm.',
        ),
    ])
    ->selected('auth_features', 'email-verification',
        then: function (Chisel $c) {
            $c->files(
                'resources/js/pages/settings/Profile.vue',
                'app/Providers/FortifyServiceProvider.php',
            )->removeSectionMarkers('chisel-email-verification');
        },
        else: function (Chisel $c) {
            $c->phpFile('app/Models/User.php')
                ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
                ->removeInterface('MustVerifyEmail');

            $c->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'resources/js/pages/settings/Profile.vue',
            )->removeSection('chisel-email-verification');

            $c->files(
                'resources/js/pages/auth/VerifyEmail.vue',
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
                'resources/js/pages/settings/Security.vue',
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
                'resources/js/pages/settings/Security.vue',
                'resources/js/types/auth.ts',
            )->removeSection('chisel-2fa');

            $c->npm()->remove('vue-input-otp');

            $c->files(
                'resources/js/pages/auth/TwoFactorChallenge.vue',
                'resources/js/components/TwoFactorSetupModal.vue',
                'resources/js/components/TwoFactorRecoveryCodes.vue',
                'resources/js/components/ui/input-otp/index.ts',
                'resources/js/components/ui/input-otp/InputOTP.vue',
                'resources/js/components/ui/input-otp/InputOTPGroup.vue',
                'resources/js/components/ui/input-otp/InputOTPSeparator.vue',
                'resources/js/components/ui/input-otp/InputOTPSlot.vue',
                'resources/js/composables/useTwoFactorAuth.ts',
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
                'resources/js/pages/settings/Security.vue',
                'resources/js/pages/auth/Login.vue',
                'resources/js/pages/auth/ConfirmPassword.vue',
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
                'resources/js/pages/settings/Security.vue',
                'resources/js/pages/auth/Login.vue',
                'resources/js/pages/auth/ConfirmPassword.vue',
            )->removeSection('chisel-passkeys');

            $c->npm()->remove('@laravel/passkeys');

            $c->files(
                'resources/js/components/PasskeyItem.vue',
                'resources/js/components/PasskeyRegister.vue',
                'resources/js/components/PasskeyVerify.vue',
                'database/migrations/2024_01_01_000000_create_passkeys_table.php',
            )->delete();
        },
    )
    ->selected('auth_features', 'password-confirmation',
        then: function (Chisel $c) {
            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSectionMarkers('chisel-password-confirmation');
        },
        else: function (Chisel $c) {
            $c->file('config/fortify.php')
                ->replace("'confirmPassword' => true,", "'confirmPassword' => false,");

            $c->phpFile('app/Http/Controllers/Settings/SecurityController.php')
                ->removeImport('Illuminate\Routing\Controllers\HasMiddleware')
                ->removeImport('Illuminate\Routing\Controllers\Middleware')
                ->removeInterface('HasMiddleware');

            $c->files(
                'app/Providers/FortifyServiceProvider.php',
                'app/Http/Controllers/Settings/SecurityController.php',
                'tests/Feature/Settings/SecurityTest.php',
            )->removeSection('chisel-password-confirmation');

            $c->files(
                'resources/js/pages/auth/ConfirmPassword.vue',
                'tests/Feature/Auth/PasswordConfirmationTest.php',
            )->delete();
        },
    )
    ->apply(function (Chisel $c): void {
        restoreAlphabetizedImportOrdering($c);
        formatFiles();
    });
