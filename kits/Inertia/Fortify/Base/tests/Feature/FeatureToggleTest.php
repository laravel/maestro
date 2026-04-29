<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_payload_is_safe_when_registration_is_disabled(): void
    {
        $features = config('fortify.features', []);

        config(['fortify.features' => array_values(array_diff($features, [Features::registration()]))]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('{{auth_login}}')
                ->where('canRegister', false)
                ->where('registerUrl', null),
            );
    }

    public function test_login_payload_is_safe_when_password_resets_are_disabled(): void
    {
        $features = config('fortify.features', []);

        config(['fortify.features' => array_values(array_diff($features, [Features::resetPasswords()]))]);

        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('{{auth_login}}')
                ->where('canResetPassword', false)
                ->where('forgotPasswordUrl', null),
            );
    }

    public function test_profile_payload_is_safe_when_email_verification_is_disabled(): void
    {
        $features = config('fortify.features', []);

        config(['fortify.features' => array_values(array_diff($features, [Features::emailVerification()]))]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('{{profile_settings}}')
                ->where('verificationSendUrl', null),
            );
    }

    public function test_security_payload_is_safe_when_two_factor_authentication_is_disabled(): void
    {
        $features = config('fortify.features', []);

        config(['fortify.features' => array_values(array_diff($features, [Features::twoFactorAuthentication()]))]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('security.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('{{security_settings}}')
                ->where('canManageTwoFactor', false)
                ->where('twoFactorUrls', null),
            );
    }
}
