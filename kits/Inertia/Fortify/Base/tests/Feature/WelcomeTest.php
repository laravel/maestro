<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_screen_can_be_rendered(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $featureEnabled = Features::enabled(Features::registration());

        $response->assertInertia(fn (Assert $page) => $page
            ->component('{{welcome}}')
            ->where('canRegister', $featureEnabled)
            ->where('registerUrl', $featureEnabled ? route('register') : null),
        );
    }

    public function test_welcome_screen_omits_register_url_when_registration_is_disabled(): void
    {
        config(['fortify.features' => []]);

        $response = $this->get(route('home'));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('{{welcome}}')
            ->where('canRegister', false)
            ->where('registerUrl', null),
        );
    }
}
