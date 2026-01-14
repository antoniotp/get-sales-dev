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

class WhatsAppAudioWebhookTest extends TestCase
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

        // Payload for an audio message
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
                                        'id' => 'wamid.HBgNNTIxMjIyMTkzMTY2MxUCABIYFDNBQzE5N0M1RTgzOUU1MzAyNEI4AA==',
                                        'timestamp' => '1767833255',
                                        'type' => 'audio',
                                        'audio' => [
                                            'mime_type' => 'audio/ogg; codecs=opus',
                                            'sha256' => 'LSXBom4ee/7S+MeDCpAXL98JZu8kElN1ReaI3xvE1Fw=',
                                            'id' => '1600761914602748',
                                            'voice' => true,
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

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_processes_an_audio_message_webhook_and_creates_records(): void
    {
        // Arrange
        $dummyMediaId = $this->payload['entry'][0]['changes'][0]['value']['messages'][0]['audio']['id'];
        $dummyMediaUrl = 'https://dummy.url/audio.ogg';
        $dummyMimeType = 'audio/ogg; codecs=opus';
        $dummyFileData = 'dummy_audio_binary_data';

        $whatsAppServiceMock = Mockery::mock(WhatsAppServiceInterface::class);
        $this->app->instance(WhatsAppServiceInterface::class, $whatsAppServiceMock);

        $whatsAppServiceMock->shouldReceive('getMediaInfo')
            ->once()
            ->with($dummyMediaId, Mockery::on(function ($arg) {
                return $arg->id === $this->chatbotChannel->id;
            }))
            ->andReturn(['url' => $dummyMediaUrl, 'mime_type' => $dummyMimeType]);

        $whatsAppServiceMock->shouldReceive('downloadMedia')
            ->once()
            ->with($dummyMediaUrl, Mockery::on(function ($arg) {
                return $arg->id === $this->chatbotChannel->id;
            }))
            ->andReturn($dummyFileData);

        // Assert initial state
        $this->assertEquals(0, Conversation::count());
        $this->assertEquals(0, Message::count());

        // Act
        $response = $this->postJson(route('webhook.whatsapp_business'), $this->payload);

        // Assert
        $response->assertStatus(204);
        $this->assertEquals(1, Conversation::count());
        $this->assertEquals(1, Message::count());

        $conversation = Conversation::first();
        $this->assertEquals($this->chatbotChannel->id, $conversation->chatbot_channel_id);
        $this->assertEquals('5212222393661', $conversation->external_conversation_id);
        $this->assertEquals('Test User', $conversation->contact_name);

        $message = Message::first();
        $this->assertEquals($conversation->id, $message->conversation_id);
        $this->assertEquals('', $message->content); // Audio messages have no caption/body
        $this->assertEquals('audio', $message->content_type);
        $this->assertEquals('incoming', $message->type);
        $this->assertEquals('contact', $message->sender_type);
        $this->assertNotNull($message->media_url);
        $this->assertTrue($message->metadata['voice']);

        $files = Storage::disk('public')->files('media/'.$this->chatbot->id);
        $this->assertCount(1, $files, 'Expected exactly one file to be created in the media directory.');
        $filePath = $files[0];

        Storage::disk('public')->assertExists($filePath);

        $this->assertEquals(Storage::disk('public')->url($filePath), $message->media_url);
    }
}
