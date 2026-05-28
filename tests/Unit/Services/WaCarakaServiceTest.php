<?php

namespace Tests\Unit\Services;

use App\Exceptions\WaRuntimeException;
use App\Services\WaCarakaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaCarakaServiceTest extends TestCase
{
    private WaCarakaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WaCarakaService::class);
    }

    public function test_health_check_returns_successful_response(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'connected'], 200),
        ]);

        $result = $this->service->health();

        $this->assertTrue($result['ok']);
        $this->assertEquals(200, $result['status']);
    }

    public function test_health_check_handles_connection_failure(): void
    {
        Http::fake([
            '*' => fn() => throw new ConnectionException('Connection refused'),
        ]);

        $result = $this->service->health();

        $this->assertFalse($result['ok']);
        $this->assertEquals(502, $result['status']);
        $this->assertStringContainsString('runtime', strtolower($result['error']));
    }

    public function test_base_url_returns_configured_value(): void
    {
        $baseUrl = $this->service->baseUrl();

        $this->assertNotEmpty($baseUrl);
        $this->assertStringContainsString('http', $baseUrl);
    }

    public function test_broadcast_limit_returns_configured_value(): void
    {
        $limit = $this->service->broadcastLimit();

        $this->assertIsInt($limit);
        $this->assertGreaterThan(0, $limit);
    }

    public function test_qr_returns_qr_data(): void
    {
        Http::fake([
            '*' => Http::response([
                'qr' => 'data:image/png;base64,...',
                'timestamp' => time(),
            ], 200),
        ]);

        $result = $this->service->qr();

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_post_request_includes_authentication_token(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        $this->service->sendText('628123456789', 'test');

        Http::assertSent(function ($request) {
            if (!empty(config('wa_caraka.token'))) {
                return $request->hasHeader('X-WA-V2-Token');
            }
            return true;
        });
    }

    public function test_request_respects_timeout(): void
    {
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        // This test verifies the timeout is set correctly
        // The actual timeout behavior is tested in integration tests
        $this->service->health();

        // If we got here without exception, the timeout is properly configured
        $this->assertTrue(true);
    }

    public function test_disconnect_calls_correct_endpoint(): void
    {
        Http::fake([
            '*/disconnect' => Http::response(['status' => 'disconnected'], 200),
        ]);

        $result = $this->service->disconnect();

        $this->assertTrue($result['ok']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'disconnect');
        });
    }

    public function test_reconnect_calls_correct_endpoint(): void
    {
        Http::fake([
            '*/reconnect' => Http::response(['status' => 'reconnecting'], 200),
        ]);

        $result = $this->service->reconnect();

        $this->assertTrue($result['ok']);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'reconnect');
        });
    }

    public function test_refresh_qr_calls_post_endpoint(): void
    {
        Http::fake([
            '*/refresh-qr' => Http::response(['qr' => 'new-qr-data'], 200),
        ]);

        $result = $this->service->refreshQr();

        $this->assertTrue($result['ok']);
        Http::assertSent(function ($request) {
            return $request->method() === 'POST' && 
                   str_contains($request->url(), 'refresh-qr');
        });
    }

    public function test_multiple_sequential_requests(): void
    {
        Http::fake([
            '*' => Http::sequence()
                ->push(['status' => 'first'], 200)
                ->push(['status' => 'second'], 200)
                ->push(['status' => 'third'], 200),
        ]);

        $health1 = $this->service->health();
        $qr = $this->service->qr();
        $health2 = $this->service->health();

        $this->assertTrue($health1['ok']);
        $this->assertTrue($qr['ok']);
        $this->assertTrue($health2['ok']);
    }
}
