<?php

namespace Tests\Feature\Docs;

use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ScalarDocsTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $cachedOpenApi = null;

    public function test_openapi_document_endpoint_serves_a_valid_spec(): void
    {
        $payload = $this->openApi();

        $this->assertMatchesRegularExpression('/^3\.1\.\d+$/', $payload['openapi'] ?? '');
        $this->assertArrayHasKey('info', $payload);
        $this->assertArrayHasKey('paths', $payload);
        $this->assertArrayHasKey('components', $payload);
    }

    public function test_scalar_docs_ui_renders_at_docs(): void
    {
        $response = $this->get('/docs');

        $response->assertOk();
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));

        $body = stripslashes((string) $response->getContent());

        $this->assertStringContainsString('/docs/api.json', $body);
        $this->assertStringNotContainsString('/'.'scr'.'ibe-source.openapi', $body);
        $this->assertStringContainsString('@scalar/api-reference', $body);
        $this->assertStringContainsString('Scalar.createApiReference', $body);
        $this->assertStringContainsString('customFetch', $body);
    }

    public function test_scalar_docs_ui_uses_configured_title(): void
    {
        $expected = config('scramble.ui.title');

        $this->assertNotEmpty($expected);

        $this->get('/docs')->assertSee($expected, false);
    }

    public function test_docs_document_endpoint_can_be_denied_by_gate(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        Gate::define('viewApiDocs', fn (): bool => false);

        $this->getJson('/docs/api.json')->assertForbidden();
    }

    public function test_docs_are_accessible_by_default_in_local_environments(): void
    {
        $this->openApi();
    }

    /** @return array<string, mixed> */
    private function openApi(): array
    {
        if ($this->cachedOpenApi !== null) {
            return $this->cachedOpenApi;
        }

        $response = $this->getJson('/docs/api.json');

        $response->assertOk();
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        return $this->cachedOpenApi = $response->json();
    }
}
