<?php

use Illuminate\Support\Facades\Gate;

it('documents the public rest api as openapi', function () {
    Gate::define('viewApiDocs', fn ($user = null): bool => true);

    $response = $this->getJson('/docs/api.json');

    $response->assertSuccessful()
        ->assertJsonPath('info.title', 'DocCompiler API')
        ->assertJsonPath('info.version', 'v1')
        ->assertJsonPath('components.securitySchemes.http.type', 'http')
        ->assertJsonPath('components.securitySchemes.http.scheme', 'bearer')
        ->assertJsonPath('security.0.http', []);

    $paths = $response->json('paths');

    expect(array_keys($paths))->toContain(
        '/health',
        '/v1/documents',
        '/v1/documents/{uuid}',
        '/v1/documents/{uuid}/content',
    )
        ->and($paths['/health']['get']['security'])->toBe([])
        ->and($paths['/v1/documents']['post']['security'] ?? null)->toBeNull();
});
