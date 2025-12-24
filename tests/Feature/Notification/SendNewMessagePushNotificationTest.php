<?php

namespace Tests\Feature\Notification;

use App\Enums\Chatbot\AgentVisibility;
use App\Events\Message\NewMessageReceived;
use App\Models\Chatbot;
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
    public function it_notifies_assigned_user_when_visibility_is_assigned_only(): void
    {
        // --- Arrange ---
        Notification::fake();

        $assignedUser = User::where('email', 'agent@example.com')->firstOrFail();
        $chatbot = Chatbot::find(2); // Chatbot with 'assigned_only' visibility
        $this->createPushSubscription($assignedUser);
        $message = $this->createTestMessage($chatbot, $assignedUser);

        // --- Act ---
        NewMessageReceived::dispatch($message);

        // --- Assert ---
        Notification::assertSentTo($assignedUser, NewMessagePushNotification::class);
    }

    #[Test]
    public function it_notifies_managers_when_visibility_is_assigned_only(): void
    {
        // --- Arrange ---
        Notification::fake();

        $assignedUser = User::where('email', 'agent@example.com')->firstOrFail();
        $managerUser = User::find(1); // Owner, level > 40
        $chatbot = Chatbot::find(2); // Chatbot with 'assigned_only' visibility
        $this->createPushSubscription($assignedUser);
        $this->createPushSubscription($managerUser);
        $message = $this->createTestMessage($chatbot, $assignedUser);

        // --- Act ---
        NewMessageReceived::dispatch($message);

        // --- Assert ---
        Notification::assertSentTo($managerUser, NewMessagePushNotification::class);
    }

    #[Test]
    public function it_notifies_all_subscribed_org_users_when_visibility_is_all(): void
    {
        // --- Arrange ---
        Notification::fake();

        $user1 = User::find(1); // Owner
        $user2 = User::where('email', 'agent@example.com')->firstOrFail(); // Agent
        $chatbot = Chatbot::find(1); // Chatbot with 'all' visibility by default
        $chatbot->update(['agent_visibility' => AgentVisibility::ALL]);

        $this->createPushSubscription($user1);
        $this->createPushSubscription($user2);
        $message = $this->createTestMessage($chatbot, null);

        // --- Act ---
        NewMessageReceived::dispatch($message);

        // --- Assert ---
        Notification::assertSentTo($user1, NewMessagePushNotification::class);
        Notification::assertSentTo($user2, NewMessagePushNotification::class);
    }

    #[Test]
    public function it_does_not_notify_users_from_other_organizations(): void
    {
        // --- Arrange ---
        Notification::fake();

        $userFromOtherOrg = User::find(2); // Belongs to Org 2
        $chatbot = Chatbot::find(1); // Belongs to Org 1
        $chatbot->update(['agent_visibility' => AgentVisibility::ALL]);

        $this->createPushSubscription($userFromOtherOrg);
        $message = $this->createTestMessage($chatbot, null);

        // --- Act ---
        NewMessageReceived::dispatch($message);

        // --- Assert ---
        Notification::assertNotSentTo($userFromOtherOrg, NewMessagePushNotification::class);
    }

    /**
     * Helper to create a PushSubscription for a user.
     */
    private function createPushSubscription(User $user): void
    {
        $user->pushSubscriptions()->create([
            'endpoint' => 'https://example.com/some-endpoint/'.$user->id,
            'public_key' => 'test_public_key_'.$user->id,
            'auth_token' => 'test_auth_token_'.$user->id,
            'content_encoding' => 'aesgcm',
        ]);
    }

    /**
     * Helper to create a message for a given chatbot and optional assigned user.
     */
    private function createTestMessage(Chatbot $chatbot, ?User $assignedUser): Message
    {
        $chatbotChannel = $chatbot->chatbotChannels->first();

        $contact = Contact::factory()->create(['organization_id' => $chatbot->organization_id]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
        ]);

        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
            'assigned_user_id' => $assignedUser?->id,
        ]);

        return Message::create([
            'conversation_id' => $conversation->id,
            'type' => 'incoming',
            'content' => 'This is a test message.',
            'content_type' => 'text',
            'sender_type' => 'contact',
        ]);
    }
}
