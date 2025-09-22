<?php

namespace Tests\Feature\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WhatsAppWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_a_valid_webhook_event_and_calls_the_service(): void
    {
        // Arrange
        $payload = [
            'event_type' => 'qr',
            'session_id' => 'test-session-123',
            'qr' => 'some-qr-code-string',
        ];

        // Mock the service
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('handle')
                ->once()
                ->withArgs(function ($data) use ($payload) {
                    return $data['event_type'] === $payload['event_type']
                        && $data['session_id'] === $payload['session_id']
                        && isset($data['qr']);
                });
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertOk();
        $response->assertJson(['status' => 'received']);
    }

    public function test_it_rejects_invalid_webhook_if_event_type_is_missing(): void
    {
        // Arrange: Missing event_type
        $payload = [
            'session_id' => 'test-session-123',
        ];

        // Ensure the service is never called
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('handle');
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertStatus(422); // Unprocessable Entity for validation failure
        $response->assertJsonValidationErrors(['event_type']);
    }

    public function test_it_rejects_invalid_webhook_if_session_id_is_missing(): void
    {
        // Arrange: Missing session_id
        $payload = [
            'event_type' => 'qr',
        ];

        // Ensure the service is never called
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('handle');
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['session_id']);
    }
}
