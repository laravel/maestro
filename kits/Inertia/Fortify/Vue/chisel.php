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

    if (! is_dir(__DIR__.'/node_modules')) {
        return;
    }

    if (runCommand(['npm', 'run', 'format']) !== 0) {
        exit(1);
    }
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
        $c->files(
            'resources/js/pages/settings/Profile.vue',
            'app/Providers/FortifyServiceProvider.php',
        )->removeSectionMarkers('email-verification');
    },
    else: function (Chisel $c) {
        $c->phpFile('app/Models/User.php')
            ->removeImport('Illuminate\Contracts\Auth\MustVerifyEmail')
            ->removeInterface('MustVerifyEmail');

        $c->file('config/fortify.php')->removeLinesContaining('Features::emailVerification()');

        $c->files(
            'app/Providers/FortifyServiceProvider.php',
            'resources/js/pages/settings/Profile.vue',
        )->removeSection('email-verification');

        $c->files(
            'resources/js/pages/auth/VerifyEmail.vue',
            'tests/Feature/Auth/EmailVerificationTest.php',
            'tests/Feature/Auth/VerificationNotificationTest.php',
        )->delete();
    },
);

$c->selected('auth_features', '2fa',
    then: function (Chisel $c) {
        $c->files(
            'app/Models/User.php',
            'database/factories/UserFactory.php',
            'resources/js/pages/settings/Security.vue',
            'resources/js/types/auth.ts',
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'app/Http/Controllers/Settings/SecurityController.php',
        )->removeSectionMarkers('2fa');
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
        )->removeSection('2fa');

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
);

$c->selected('auth_features', 'passkeys',
    then: function (Chisel $c) {
        $c->files(
            'config/fortify.php',
            'app/Providers/FortifyServiceProvider.php',
            'app/Http/Controllers/Settings/SecurityController.php',
            'tests/Feature/Settings/SecurityTest.php',
            'resources/js/pages/settings/Security.vue',
            'resources/js/pages/auth/Login.vue',
            'resources/js/pages/auth/ConfirmPassword.vue',
        )->removeSectionMarkers('passkeys');
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
        )->removeSection('passkeys');

        $c->npm()->remove('@laravel/passkeys');

        $c->files(
            'resources/js/components/PasskeyItem.vue',
            'resources/js/components/PasskeyRegister.vue',
            'resources/js/components/PasskeyVerify.vue',
            'database/migrations/2024_01_01_000000_create_passkeys_table.php',
        )->delete();
    },
);

formatFiles();
