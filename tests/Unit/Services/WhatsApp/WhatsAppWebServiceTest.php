<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Services\WhatsApp\WhatsAppWebService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppWebServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.wwebjs_service.url' => 'http://fake-node-service.test']);
    }

    public function test_successful_start_a_session()
    {
        Http::fake([
            '*/sessions' => Http::response(['status' => 'success'], 200),
        ]);
        $sessionId = 'test-session-123';

        $wwebjsService = new WhatsAppWebService();
        $result = $wwebjsService->startSession($sessionId);

        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($sessionId) {
            $wwebjsUrl = config('services.wwebjs_service.url');
            return $request->url() === "{$wwebjsUrl}/sessions" && $request['externalId'] === $sessionId;
        });
    }

    public function test_return_false_when_wwebjs_url_is_not_configured()
    {
        config(['services.wwebjs_service.url' => null]);
        Http::fake();
        Log::shouldReceive('error')->once()->with('WhatsApp Web Service URL is not configured. Please check config/services.php and your .env file.');
        $wwebjsService = new WhatsAppWebService();
        $result = $wwebjsService->startSession('test-session-123');
        $this->assertFalse($result);
        Http::assertNothingSent();
    }

    public function test_return_false_and_logs_error_on_api_failure()
    {
        Http::fake([
            '*/sessions' => Http::response('Server error', 500)
        ]);

        Log::shouldReceive('error')->once();

        $sessionId = 'test-session-failure';
        $service = new WhatsAppWebService();

        $result = $service->startSession($sessionId);
        $this->assertFalse($result);
    }


}
