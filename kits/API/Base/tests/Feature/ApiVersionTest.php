<?php

namespace Tests\Feature;

use App\Enums\ApiVersion;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiVersionTest extends TestCase
{
    private function registerVersionRoute(): void
    {
        Route::get('/api-version-test', fn () => response()->json([
            'version' => ApiVersion::requested()->value,
        ]));
    }

    public function test_requests_default_to_v1_when_no_version_header_is_sent(): void
    {
        $this->registerVersionRoute();

        $this->getJson('/api-version-test')
            ->assertOk()
            ->assertJsonPath('version', 'v1');
    }

    public function test_requests_default_to_v1_when_version_header_is_empty(): void
    {
        $this->registerVersionRoute();

        $this->withHeader('X-API-Version', '')
            ->getJson('/api-version-test')
            ->assertOk()
            ->assertJsonPath('version', 'v1');
    }

    public function test_supported_version_header_resolves_to_that_version(): void
    {
        $this->registerVersionRoute();

        $this->withHeader('X-API-Version', 'v1')
            ->getJson('/api-version-test')
            ->assertOk()
            ->assertJsonPath('version', 'v1');
    }

    public function test_unsupported_explicit_version_returns_not_acceptable_response(): void
    {
        $this->registerVersionRoute();

        $this->withHeader('X-API-Version', 'v2')
            ->getJson('/api-version-test')
            ->assertStatus(406)
            ->assertJsonPath('message', 'Unsupported API version [v2].');
    }
}
