<?php

namespace Tests\Feature\Docs;

use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    /** @var array<string, mixed>|null */
    private ?array $cachedSpec = null;

    public function test_openapi_endpoint_serves_a_valid_3_1_spec(): void
    {
        $payload = $this->fetchSpec();

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('openapi', $payload);
        $this->assertStringStartsWith('3.1', (string) $payload['openapi']);
        $this->assertArrayHasKey('info', $payload);
        $this->assertArrayHasKey('paths', $payload);
        $this->assertArrayHasKey('components', $payload);
    }

    public function test_bearer_security_scheme_is_registered_and_applied(): void
    {
        $spec = $this->fetchSpec();

        $schemes = $spec['components']['securitySchemes'] ?? [];
        $this->assertNotEmpty($schemes, 'No security schemes are registered.');

        $bearer = collect($schemes)->first(
            fn (array $scheme): bool => ($scheme['type'] ?? null) === 'http'
                && ($scheme['scheme'] ?? null) === 'bearer',
        );
        $this->assertNotNull($bearer, 'Bearer (HTTP) security scheme is missing.');

        $globalSecurity = $spec['security'] ?? [];
        $this->assertNotEmpty(
            $globalSecurity,
            'Top-level security requirement should be set so authenticated routes inherit it.',
        );

        $bearerSchemeName = collect($schemes)
            ->search(fn (array $scheme): bool => ($scheme['type'] ?? null) === 'http'
                && ($scheme['scheme'] ?? null) === 'bearer');

        $referencesBearer = collect($globalSecurity)
            ->contains(fn (array $requirement): bool => array_key_exists($bearerSchemeName, $requirement));

        $this->assertTrue($referencesBearer, 'Top-level security must reference the bearer scheme.');
    }

    public function test_scalar_docs_ui_renders_at_docs(): void
    {
        $response = $this->get('/docs');

        $response->assertOk();
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));

        $body = stripslashes((string) $response->getContent());

        $this->assertStringContainsString('/docs/api.json', $body);
        $this->assertStringContainsString('@scalar/api-reference', $body);
    }

    public function test_view_api_docs_gate_can_block_access(): void
    {
        Gate::define('viewApiDocs', fn (): bool => false);

        $this->get('/docs/api.json')->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function fetchSpec(): array
    {
        if ($this->cachedSpec !== null) {
            return $this->cachedSpec;
        }

        $response = $this->get('/docs/api.json');

        $response->assertOk();
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));

        return $this->cachedSpec = $response->json();
    }
}
