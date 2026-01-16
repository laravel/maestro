<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Starter Kits
    |--------------------------------------------------------------------------
    |
    | This option defines the available Starter Kits.
    |
    */
    'starter_kits' => [
        'Livewire',
        'React',
        'Vue',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Components
    |--------------------------------------------------------------------------
    |
    | This option defines the UI components used for different
    | Starter Kits that Maestro uses during the build.
    |
    */
    'ui_components' => [
        // Base
        'dashboard' => [
            'react' => 'dashboard',
            'vue' => 'Dashboard',
        ],
        'welcome' => [
            'react' => 'welcome',
            'vue' => 'Welcome',
        ],

        // Auth
        'auth_confirm_password' => [
            'react' => 'auth/confirm-password',
            'vue' => 'auth/ConfirmPassword',
        ],
        'auth_forgot_password' => [
            'react' => 'auth/forgot-password',
            'vue' => 'auth/ForgotPassword',
        ],
        'auth_login' => [
            'react' => 'auth/login',
            'vue' => 'auth/Login',
        ],
        'auth_register' => [
            'react' => 'auth/register',
            'vue' => 'auth/Register',
        ],
        'auth_reset_password' => [
            'react' => 'auth/reset-password',
            'vue' => 'auth/ResetPassword',
        ],
        'auth_two_factor_challenge' => [
            'react' => 'auth/two-factor-challenge',
            'vue' => 'auth/TwoFactorChallenge',
        ],
        'auth_verify_email' => [
            'react' => 'auth/verify-email',
            'vue' => 'auth/VerifyEmail',
        ],

        // Settings
        'appearance_settings' => [
            'react' => 'settings/appearance',
            'vue' => 'settings/Appearance',
        ],
        'password_settings' => [
            'react' => 'settings/password',
            'vue' => 'settings/Password',
        ],
        'profile_settings' => [
            'react' => 'settings/profile',
            'vue' => 'settings/Profile',
        ],
        'two_factor_settings' => [
            'react' => 'settings/two-factor',
            'vue' => 'settings/TwoFactor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WorkOS Variant
    |--------------------------------------------------------------------------
    |
    | This option defines the customizations needed for the WorkOS
    | starter kits variations.
    |
    */
    'workos' => [
        'ignore' => [
            'app/Actions/',
            'app/Concerns/',
            'app/Http/Controllers/Settings/PasswordController.php',
            'app/Http/Requests/Settings/PasswordUpdateRequest.php',
            'app/Http/Requests/Settings/ProfileDeleteRequest.php',
            'app/Providers/FortifyServiceProvider.php',
            'config/fortify.php',
            'database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php',
            'resources/js/components/alert-error.tsx',
            'resources/js/components/TwoFactorRecoveryCodes.vue',
            'resources/js/components/TwoFactorSetupModal.vue',
            'resources/js/components/ui/input-otp.tsx',
            'resources/js/components/ui/input-otp/',
            'resources/js/components/ui/spinner.tsx',
            'resources/js/hooks/use-clipboard.ts',
            'resources/js/hooks/use-two-factor-auth.ts',
            'resources/js/layouts/auth/',
            'resources/js/layouts/AuthLayout.vue',
            'resources/js/pages/auth/',
            'resources/js/pages/settings/Password.vue',
            'resources/js/pages/settings/TwoFactor.vue',
            'tests/Feature/Auth/',
            'tests/Feature/ExampleTest.php',
            'tests/Feature/Settings/PasswordUpdateTest.php',
            'tests/Feature/Settings/TwoFactorAuthenticationTest.php',
        ],
        'service' => [
            'client_id' => env('WORKOS_CLIENT_ID'),
            'secret' => env('WORKOS_API_KEY'),
            'redirect_url' => env('WORKOS_REDIRECT_URL'),
        ],
    ],
];
