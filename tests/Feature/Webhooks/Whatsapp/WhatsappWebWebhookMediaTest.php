<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsappWebWebhookMediaTest extends TestCase
{
    use RefreshDatabase;

    protected Chatbot $chatbot;

    protected array $messagePayload;

    protected array $mediaPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->chatbot = Chatbot::find(1);

        $channel = Channel::where('slug', 'whatsapp-web')->first();
        $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Web Channel 1',
            'credentials' => ['phone_number' => '+3333333333'],
            'status' => 1,
        ]);

        Event::fake();

        // Initialize payloads from internal helper methods
        $this->messagePayload = $this->getMinimalMessagePayload();
        $this->mediaPayload = $this->getMinimalMediaPayload();

        // Set the session ID in the payloads to match our test chatbot
        $sessionId = 'chatbot-'.$this->chatbot->id;
        $this->messagePayload['sessionId'] = $sessionId;
        $this->mediaPayload['sessionId'] = $sessionId;

        // Ensure both payloads share the same external ID
        $externalId = 'true_5212220001111@c.us_3EB097432984A9523B41';
        $this->messagePayload['data']['message']['id']['_serialized'] = $externalId;
        $this->mediaPayload['data']['message']['id']['_serialized'] = $externalId;
    }

    /**
     * Helper function to simulate a webhook call.
     */
    protected function postWebhook(array $payload): TestResponse
    {
        return $this->postJson(route('webhook.whatsapp_web'), $payload);
    }

    public function test_it_creates_message_on_first_payload_and_updates_with_media_on_second(): void
    {
        // Arrange: Prepare fakes
        Storage::fake('public');

        // Act 1: Send the first payload (dataType: message with hasMedia:true)
        $response1 = $this->postWebhook($this->messagePayload);

        // Assert 1: Check that a message was created but without media details
        $response1->assertOk();
        $this->assertDatabaseCount('messages', 1);

        $message = Message::first();
        $expectedExternalId = $this->messagePayload['data']['message']['id']['_serialized'];

        $this->assertEquals($expectedExternalId, $message->external_message_id);
        $this->assertEquals($this->messagePayload['data']['message']['body'], $message->content);
        $this->assertNull($message->media_url);
        $this->assertEquals('pending', $message->content_type);

        // Act 2: Send the second payload (dataType: media)
        $response2 = $this->postWebhook($this->mediaPayload);
        Log::info('external_id: '.$message->external_message_id);
        // Assert 2: Check that the original message was updated with media details
        $response2->assertOk();
        $this->assertDatabaseCount('messages', 1); // Ensure no new message was created

        $message->refresh();

        $this->assertNotNull($message->media_url, 'media_url should now be populated');
        $this->assertEquals('image', $message->content_type);
        Storage::disk('public')->assertExists($message->media_url);
    }

    private function getMinimalMessagePayload(): array
    {
        return [
            'dataType' => 'message',
            'data' => [
                'message' => [
                    'id' => ['_serialized' => ''], // This will be overwritten in setUp
                    'from' => '5212220001111@c.us',
                    'notifyName' => 'Test User',
                    'type' => 'image',
                    'timestamp' => 1761010425,
                    'hasMedia' => true,
                    'caption' => 'This is the caption.',
                    'body' => 'This is the caption.', // For consistency, as user pointed out
                ],
            ],
        ];
    }

    private function getMinimalMediaPayload(): array
    {
        return [
            'dataType' => 'media',
            'data' => [
                'message' => [
                    'id' => ['_serialized' => ''], // This will be overwritten in setUp
                    'type' => 'image',
                    'timestamp' => 1761010425,
                ],
                'messageMedia' => [
                    'mimetype' => 'image/jpeg',
                    // Fake base64 data for an image
                    'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/wcAAwAB/epv2AAAAABJRU5ErkJggg==',
                ],
            ],
        ];
    }
}
