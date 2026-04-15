<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_successful_response(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
    }

    public function test_root_redirects_to_health_endpoint(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/up');
    }
}
