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
    if (runCommand(['composer', 'lint']) !== 0) {
        exit(1);
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
        'password-confirmation' => 'Password confirmation',
    ], ['password-confirmation'], hint: 'Use space to select, enter to confirm.');

$c->selected('auth_features', 'email-verification',
    then: function (Chisel $c) {
        $c->files(
            'resources/js/pages/settings/profile.tsx',
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
            'resources/js/pages/settings/profile.tsx',
        )->removeSection('chisel-email-verification');

        $c->files(
            'resources/js/pages/auth/verify-email.tsx',
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
            'database/migrations/2024_01_01_000000_create_passkeys_table.php',
        )->delete();
    },
);

$c->selectedAny('auth_features', ['2fa', 'passkeys'],
    then: function (Chisel $c) {
        $c->file('resources/js/pages/settings/security.tsx')
            ->removeSectionMarkers('chisel-2fa-or-passkeys');
    },
    else: function (Chisel $c) {
        $c->file('resources/js/pages/settings/security.tsx')
            ->removeSection('chisel-2fa-or-passkeys');
    },
);

$c->selected('auth_features', 'password-confirmation',
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
            'resources/js/pages/auth/confirm-password.tsx',
            'tests/Feature/Auth/PasswordConfirmationTest.php',
        )->delete();
    },
);

formatFiles();
