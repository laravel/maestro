<?php

namespace Tests\Feature\Docs;

use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ScalarDocsTest extends TestCase
{
    private function decodeOpenApi(string $body): array
    {
        $trimmed = ltrim($body);

        if (str_starts_with($trimmed, '{')) {
            return json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        }

        return Yaml::parse($body);
    }

    public function test_scribe_openapi_endpoint_serves_a_valid_spec(): void
    {
        $response = $this->get('/scribe-source.openapi');

        $response->assertOk();

        $payload = $this->decodeOpenApi($response->streamedContent() ?: $response->getContent());

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('openapi', $payload);
        $this->assertArrayHasKey('info', $payload);
        $this->assertArrayHasKey('paths', $payload);
    }

    public function test_scribe_openapi_spec_lists_kit_routes(): void
    {
        $response = $this->get('/scribe-source.openapi');

        $payload = $this->decodeOpenApi($response->streamedContent() ?: $response->getContent());

        $paths = array_keys($payload['paths'] ?? []);

        $this->assertContains('/login', $paths);
        $this->assertContains('/register', $paths);
        $this->assertContains('/me', $paths);
    }

    public function test_scalar_docs_ui_renders_at_docs(): void
    {
        $response = $this->get('/docs');

        $response->assertOk();
        $this->assertStringContainsString('text/html', (string) $response->headers->get('Content-Type'));

        $body = $response->getContent();

        $this->assertStringContainsString('/scribe-source.openapi', $body);
        $this->assertStringContainsString('@scalar/api-reference', $body);
    }

    public function test_scalar_docs_ui_uses_configured_title(): void
    {
        $expected = config('app.name').' API Reference';

        $this->get('/docs')->assertSee($expected, false);
    }

    public function test_scalar_docs_ui_does_not_expose_scribe_markers(): void
    {
        $body = $this->get('/docs')->getContent();

        $this->assertStringNotContainsString('Knuckles\\Scribe', $body);
        $this->assertStringNotContainsString('scribe-style', $body);
    }
}
