<?php

namespace Tests\Feature\Webhooks\Whatsapp;

use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class WhatsAppImageWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Chatbot $chatbot;

    private ChatbotChannel $chatbotChannel;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->chatbot = Chatbot::find(1);

        $channel = Channel::where('slug', 'whatsapp')->firstOrFail();

        $phoneNumberId = '918518644364527';
        $this->chatbotChannel = $this->chatbot->chatbotChannels()->create([
            'channel_id' => $channel->id,
            'name' => 'WhatsApp Channel 1',
            'credentials' => [
                'phone_number' => '+3333333333',
                'phone_number_id' => $phoneNumberId,
                'phone_number_access_token' => 'test_access_token',
                'whatsapp_business_access_token' => 'test_access_token',
            ],
            'status' => 1,
        ]);

        // Payload for an image message
        $this->payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '1630897088294109',
                    'changes' => [
                        [
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '+3333333333',
                                    'phone_number_id' => $phoneNumberId,
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Test User'],
                                        'wa_id' => '5212222393661',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '5212222393661',
                                        'id' => 'wamid.HBgNNTIxMjIyMTkzMTY2MxUCABIYFDNBOTgxRkQ0MzYyOUFFQjZGMUVEAA==',
                                        'timestamp' => '1767833024',
                                        'type' => 'image',
                                        'image' => [
                                            'caption' => 'Nueva imagen del keyboard',
                                            'mime_type' => 'image/jpeg',
                                            'sha256' => 'AzVxE5Cdu9ZND6iSgoTtu6wSiudsUn8ufSDjQd8jT7k=',
                                            'id' => '1403192074792073',
                                            'url' => 'https://lookaside.fbsbx.com/whatsapp_business/attachments/?mid=1403192074792073&source=webhook&ext=1767833325&hash=ARlzOjUiCLNcCSG7MfVqeHFAJhiFlC9f54i-rPvpZMWSA',
                                        ],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        // Mock the Storage facade to prevent actual file writes
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_processes_an_image_message_webhook_and_creates_records(): void
    {
        // Arrange
        $dummyMediaId = $this->payload['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];
        $dummyMediaUrl = 'https://dummy.url/image.jpg';
        $dummyMimeType = 'image/jpeg';
        $dummyFileData = 'dummy_image_binary_data'; // Simulate binary image data

        // Mock WhatsAppServiceInterface
        $whatsAppServiceMock = Mockery::mock(WhatsAppServiceInterface::class);
        $this->app->instance(WhatsAppServiceInterface::class, $whatsAppServiceMock);

        $whatsAppServiceMock->shouldReceive('getMediaInfo')
            ->once()
            ->with($dummyMediaId, Mockery::on(function ($arg) {
                return $arg->id === $this->chatbotChannel->id;
            }))
            ->andReturn([
                'url' => $dummyMediaUrl,
                'mime_type' => $dummyMimeType,
            ]);

        $whatsAppServiceMock->shouldReceive('downloadMedia')
            ->once()
            ->with($dummyMediaUrl, Mockery::on(function ($arg) {
                return $arg->id === $this->chatbotChannel->id;
            }))
            ->andReturn($dummyFileData);

        // Assert initial state
        $this->assertEquals(0, Conversation::count());
        $this->assertEquals(0, Message::count());

        // Act: Send the webhook payload to the controller
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert HTTP response
        $response->assertStatus(204); // No Content

        // Assert database changes
        $this->assertEquals(1, Conversation::count());
        $this->assertEquals(1, Message::count());

        $conversation = Conversation::first();
        $this->assertEquals($this->chatbotChannel->id, $conversation->chatbot_channel_id);
        $this->assertEquals('5212222393661', $conversation->external_conversation_id);
        $this->assertEquals('Test User', $conversation->contact_name);

        $message = Message::first();
        $this->assertEquals($conversation->id, $message->conversation_id);
        $this->assertEquals('Nueva imagen del keyboard', $message->content);
        $this->assertEquals('image', $message->content_type);
        $this->assertEquals('incoming', $message->type);
        $this->assertEquals('contact', $message->sender_type);
        $this->assertNotNull($message->media_url);

        $files = Storage::disk('public')->files('media/'.$this->chatbot->id);
        $this->assertCount(1, $files, 'Expected exactly one file to be created in the media directory.');
        $filePath = $files[0];

        Storage::disk('public')->assertExists($filePath);

        $this->assertEquals(Storage::disk('public')->url($filePath), $message->media_url);
    }
}
