<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Events\WhatsApp\WhatsappQrCodeReceived;
use App\Services\WhatsApp\WhatsappWebWebhookService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WhatsappWebWebhookServiceTest extends TestCase
{
    public function test_it_dispatches_qr_code_event(): void
    {
        // Arrange
        Event::fake();

        $service = new WhatsappWebWebhookService();
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'qr_code' => 'test-qr-code',
        ];

        // Act
        $service->handle($payload);

        // Assert
        Event::assertDispatched(WhatsappQrCodeReceived::class, function ($event) use ($payload) {
            return $event->sessionId === $payload['session_id'] && $event->qrCode === $payload['qr_code'];
        });
    }

    public function test_it_does_not_dispatch_qr_event_if_qr_code_is_missing(): void
    {
        // Arrange
        Event::fake();

        $service = new WhatsappWebWebhookService();
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'qr_code' => '', // empty qr code
        ];

        // Act
        $service->handle($payload);

        // Assert
        Event::assertNotDispatched(WhatsappQrCodeReceived::class);
    }

    public function test_it_handles_unknown_event_gracefully(): void
    {
        // Arrange
        $service = new WhatsappWebWebhookService();
        $payload = [
            'event_type' => 'unknown_event',
            'session_id' => 'test-session-123',
        ];

        // Act
        $service->handle($payload);

        // No assertion needed, we just want to make sure it doesn't throw an exception.
        // The service logs a warning, which we could test for, but it's not critical right now.
        $this->assertTrue(true);
    }
}
