<?php

namespace Tests\Feature\Notification;

use App\Events\Message\NewMessageReceived;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\Message\NewMessagePushNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendNewMessagePushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function new_message_triggers_push_notification_to_subscribed_user(): void
    {
        // --- Arrange ---
        Notification::fake();

        $assignedUser = User::where('email', 'agent@example.com')->firstOrFail();
        $chatbot = Chatbot::find(1);
        $chatbotChannel = ChatbotChannel::find(1);

        // Create necessary related models for the conversation
        $contact = Contact::factory()->create(['organization_id' => $chatbot->organization_id]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
        ]);

        // Create a conversation for the test
        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
            'assigned_user_id' => $assignedUser->id,
        ]);

        // Create a message for the conversation
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'incoming',
            'content' => 'This is a test message.',
            'content_type' => 'text',
            'sender_type' => 'contact',
        ]);

        // Manually create a push subscription for the agent user via the user's relationship
        $assignedUser->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/some-endpoint/456',
            'public_key' => 'test_public_key',
            'auth_token' => 'test_auth_token',
            'content_encoding' => 'aesgcm',
        ]);

        // --- Act ---
        NewMessageReceived::dispatch($message);

        // --- Assert ---
        Notification::assertSentTo($assignedUser, NewMessagePushNotification::class);
    }
}
