<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\WhatsappWebServiceDetector;
use Exception;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsappWebServiceDetectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.wwebjs_service.url' => 'http://fake-node-service.test']);
    }

    #[Test]
    public function it_detects_new_api_when_ping_responds()
    {
        Http::fake([
            '*/ping' => Http::response(['status' => 'ok'], 200),
            '*/health' => Http::response(['status' => 'ok'], 200),
        ]);
        $detector = new WhatsappWebServiceDetector;
        $detector->clearCache();
        $this->assertEquals('new', $detector->detect());
    }

    #[Test]
    public function it_detects_legacy_api_when_health_responds()
    {
        Http::fake([
            '*/ping' => Http::response([], 404),
            '*/health' => Http::response(['status' => 'ok'], 200),
        ]);
        $detector = new WhatsappWebServiceDetector;
        $detector->clearCache();
        $this->assertEquals('legacy', $detector->detect());
    }

    #[Test]
    public function it_throws_exception_when_no_api_is_detected()
    {
        Http::fake([
            '*/ping' => Http::response([], 404),
            '*/health' => Http::response([], 404),
        ]);

        $detector = new WhatsappWebServiceDetector;
        $detector->clearCache();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not determine WhatsApp Web Service API version. The service may be down.');

        $detector->detect();
    }

    #[Test]
    public function it_caches_the_detection_result()
    {
        Http::fake([
            '*/ping' => Http::response(['status' => 'ok'], 200),
        ]);

        $detector = new WhatsappWebServiceDetector;
        $detector->clearCache(); // Ensure a clean slate for this test

        // First call - should hit the endpoint
        $detector->detect();

        // Second call - should use the cache
        $detector->detect();

        Http::assertSentCount(1);
    }
}
