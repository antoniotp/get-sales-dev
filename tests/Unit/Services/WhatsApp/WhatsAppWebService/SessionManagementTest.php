<?php

namespace Tests\Unit\Services\WhatsApp\WhatsAppWebService;

use App\Models\Chatbot;
use App\Services\WhatsApp\WhatsAppWebService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config()->set('services.wwebjs_service.url', 'http://test.wwebjs.service');
        config()->set('services.wwebjs_service.key', 'test-api-key');
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $service = new WhatsAppWebService;
        $this->assertInstanceOf(WhatsAppWebService::class, $service);
    }

    #[Test]
    public function get_session_status_returns_state_when_session_is_connected()
    {
        // Arrange
        $chatbot = Chatbot::find(1);
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/session/status/'.$sessionId;

        Http::fake([
            $url => Http::response(['success' => true, 'state' => 'CONNECTED'], 200),
        ]);

        $service = new WhatsAppWebService;

        // Act
        $result = $service->getSessionStatus($chatbot);

        // Assert
        $this->assertEquals('CONNECTED', $result['status']);
    }

    #[Test]
    public function get_session_status_returns_disconnected_when_session_is_not_found()
    {
        // Arrange
        $chatbot = Chatbot::find(1);
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/session/status/'.$sessionId;

        Http::fake([
            $url => Http::response(['success' => false, 'message' => 'session_not_found'], 200),
        ]);

        $service = new WhatsAppWebService;

        // Act
        $result = $service->getSessionStatus($chatbot);

        // Assert
        $this->assertEquals('DISCONNECTED', $result['status']);
        $this->assertEquals('session_not_found', $result['message']);
    }

    #[Test]
    public function get_session_status_returns_error_on_server_error()
    {
        // Arrange
        $chatbot = Chatbot::find(1);
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/session/status/'.$sessionId;

        Http::fake([
            $url => Http::response(null, 500),
        ]);

        $service = new WhatsAppWebService;

        // Act
        $result = $service->getSessionStatus($chatbot);

        // Assert
        $this->assertEquals('ERROR', $result['status']);
        $this->assertEquals('Connection service failed.', $result['message']);
    }

    #[Test]
    public function get_session_status_returns_disconnected_on_client_error()
    {
        // Arrange
        $chatbot = Chatbot::find(1);
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/session/status/'.$sessionId;

        Http::fake([
            $url => Http::response(null, 404),
        ]);

        $service = new WhatsAppWebService;

        // Act
        $result = $service->getSessionStatus($chatbot);

        // Assert
        $this->assertEquals('DISCONNECTED', $result['status']);
    }

    #[Test]
    public function get_session_status_returns_error_on_http_exception()
    {
        // Arrange
        $chatbot = Chatbot::find(1);
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = config('services.wwebjs_service.url').'/session/status/'.$sessionId;

        Http::fake([
            $url => function () {
                throw new \Exception('Connection failed');
            },
        ]);

        $service = new WhatsAppWebService;

        // Act
        $result = $service->getSessionStatus($chatbot);

        // Assert
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Connection failed', $result['message']);
    }
}
