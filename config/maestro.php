<?php

return [

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
        ]
    ]
];
