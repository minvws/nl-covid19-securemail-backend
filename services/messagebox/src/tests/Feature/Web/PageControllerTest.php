<?php

namespace Tests\Feature\Web;

use Tests\TestCase;

class PageControllerTest extends TestCase
{
    public function testHome(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testContentTypeOptionsHeader(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function testCorsHeader(): void
    {
        $response = $this->get('/api/ping');

        $response->assertHeader('Access-Control-Allow-Origin');
    }
}
