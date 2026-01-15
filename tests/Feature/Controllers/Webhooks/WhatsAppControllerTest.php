<?php

namespace Tests\Feature\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsAppWebhookHandlerServiceInterface;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class WhatsAppControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Config::set('services.whatsapp.verify_token', 'test-token');
    }

    public function test_webhook_verification_succeeds_with_valid_token(): void
    {
        $challenge = 'random_challenge_string';

        $response = $this->get('/webhook/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test-token',
            'hub_challenge' => $challenge,
        ]));

        $response->assertStatus(200);
        $response->assertContent($challenge);
    }

    public function test_webhook_verification_fails_with_invalid_token(): void
    {
        $response = $this->get('/webhook/whatsapp?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong-token',
            'hub_challenge' => 'challenge',
        ]));

        $response->assertStatus(403);
    }

    public function test_webhook_handles_valid_message_payload(): void
    {
        $webhookHandler = Mockery::mock(WhatsAppWebhookHandlerServiceInterface::class);
        $webhookHandler->shouldReceive('process')->once();
        $this->app->instance(WhatsAppWebhookHandlerServiceInterface::class, $webhookHandler);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'from' => '1234567890',
                                        'text' => ['body' => 'Test message'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhook/whatsapp', $payload);
        $response->assertStatus(204);
    }

    public function test_webhook_handles_invalid_payload_gracefully(): void
    {
        $response = $this->postJson('/webhook/whatsapp', [
            'invalid' => 'payload',
        ]);

        $response->assertStatus(204);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
