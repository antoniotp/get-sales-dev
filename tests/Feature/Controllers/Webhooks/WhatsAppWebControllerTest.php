<?php

namespace Tests\Feature\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppWebControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_handles_a_valid_webhook_event_and_calls_the_service(): void
    {
        // Arrange
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'qr_code' => 'some-qr-code-string',
        ];

        // Mock the service
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('handle')
                ->once()
                ->withArgs(function ($data) use ($payload) {
                    return $data['event_type'] === $payload['event_type']
                        && $data['session_id'] === $payload['session_id']
                        && isset($data['qr_code']);
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
            'event_type' => 'qr_code_received',
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

    #[Test]
    public function it_handles_a_valid_data_type_webhook_and_calls_the_service(): void
    {
        // Arrange
        // Use a known valid dataType from the validation rule.
        $payload = [
            'dataType' => 'qr',
            'sessionId' => 'test-session-456',
            'data' => ['qr' => 'test-qr-code'],
        ];

        // Mock the service interface. The router is bound to this, so this mock
        // will correctly intercept the call.
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) use ($payload) {
            $mock->shouldReceive('handle')
                ->once()
                ->withArgs(function ($data) use ($payload) {
                    return $data['dataType'] === $payload['dataType']
                        && $data['sessionId'] === $payload['sessionId'];
                });
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertOk();
        $response->assertJson(['status' => 'received']);
    }

    #[Test]
    public function it_rejects_invalid_webhook_if_session_id_is_missing_with_data_type(): void
    {
        // Arrange: Missing sessionId
        $payload = [
            'dataType' => 'some_data_type',
        ];

        // Ensure the service is never called
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('handle');
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertStatus(422); // Unprocessable Entity for validation failure
        $response->assertJsonValidationErrors(['sessionId']);
    }

    #[Test]
    public function it_rejects_webhook_if_both_event_type_and_data_type_are_present(): void
    {
        // Arrange: Both keys are present, which is invalid
        $payload = [
            'event_type' => 'qr_code_received',
            'session_id' => 'test-session-123',
            'dataType' => 'some_data_type',
            'sessionId' => 'test-session-456',
        ];

        // Ensure the service is never called
        $this->mock(WhatsappWebWebhookServiceInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('handle');
        });

        // Act
        $response = $this->postJson('/webhook/whatsapp_web', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dataType', 'event_type']);
    }
}
