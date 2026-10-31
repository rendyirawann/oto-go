<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SwaggerDocsTest extends TestCase
{
    public function test_openapi_document_generates_with_core_tags_and_paths(): void
    {
        $this->assertSame(0, Artisan::call('l5-swagger:generate'));

        $path = storage_path('api-docs/api-docs.json');
        $this->assertFileExists($path);

        $doc = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('OtoGo API', $doc['info']['title']);
        // Relative server: "Try it out" targets the origin the docs are opened on.
        $this->assertSame('/', $doc['servers'][0]['url']);
        $this->assertArrayHasKey('sanctum', $doc['components']['securitySchemes']);
        $this->assertSame('bearer', $doc['components']['securitySchemes']['sanctum']['scheme']);

        $tags = array_column($doc['tags'], 'name');
        foreach (['Sistem', 'Auth', 'Profil', 'Master Data', 'Kendaraan Saya', 'Blog', 'Asisten AI'] as $tag) {
            $this->assertContains($tag, $tags);
        }

        foreach ([
            '/api/health',
            '/api/v1/auth/register',
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/me',
            '/api/v1/me/password',
            '/api/v1/vehicle-types',
            '/api/v1/vehicle-categories',
            '/api/v1/vehicle-brands',
            '/api/v1/vehicle-models',
            '/api/v1/service-areas',
            '/api/v1/price-estimates',
            '/api/v1/me/vehicles',
            '/api/v1/me/vehicles/{id}',
        ] as $route) {
            $this->assertArrayHasKey($route, $doc['paths'], "Missing path {$route}");
        }
    }

    public function test_documentation_ui_is_served(): void
    {
        $this->get('/api/documentation')->assertOk()->assertSee('swagger-ui', false);
    }
}
