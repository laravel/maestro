<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_json_response(): void
    {
        $response = $this->json('GET', '/');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson(['status' => 'up']);
    }
}
