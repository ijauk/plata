<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test-proxy', fn (Request $request): array => [
            'secure' => $request->isSecure(),
            'ip' => $request->ip(),
        ]);
    }

    public function test_forwarded_headers_are_ignored_without_trusted_proxies(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->getProxied('172.21.0.1')
            ->assertJson(['secure' => false, 'ip' => '172.21.0.1']);
    }

    public function test_forwarded_headers_are_used_from_configured_proxy(): void
    {
        config(['trustedproxy.proxies' => '192.168.1.6,172.21.0.1']);

        $this->getProxied('172.21.0.1')
            ->assertJson(['secure' => true, 'ip' => '203.0.113.7']);
    }

    public function test_forwarded_headers_are_ignored_from_unknown_proxy(): void
    {
        config(['trustedproxy.proxies' => '192.168.1.6,172.21.0.1']);

        $this->getProxied('10.0.0.9')
            ->assertJson(['secure' => false, 'ip' => '10.0.0.9']);
    }

    private function getProxied(string $proxyIp): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $proxyIp])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.7',
                'X-Forwarded-Proto' => 'https',
            ])
            ->getJson('/test-proxy');
    }
}
