<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use Tests\Feature\ControllerTestCase;

class SecurityHeadersTest extends ControllerTestCase
{
    /**
     * @dataProvider headerProvider
     */
    public function testSecurityHeaders(string $header, string $expected): void
    {
        $response = $this->get('/');
        $response->assertHeader($header, $expected);
    }


    public function headerProvider(): array
    {
        return [
            ['X-Content-Type-Options', 'nosniff'],
            ['Referrer-Policy', 'strict-origin-when-cross-origin'],
            ['X-Frame-Options', 'DENY'],
            ['X-XSS-Protection', '1; mode=block']
        ];
    }

    public function testCSPHeader(): void
    {
        $response = $this->get('/');
        $response->assertHeader('content-security-policy');
    }
}
