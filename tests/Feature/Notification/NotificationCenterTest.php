<?php

namespace Tests\Feature\Notification;

use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\Message\NewMessagePushNotification; // Using this as a dummy notification
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_fetches_user_notifications(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $user->notify(new NewMessagePushNotification($this->createDummyMessage($user->organizationUsers->first()->organization_id)));

        // --- Act ---
        $response = $this->getJson(route('notifications.index'));

        // --- Assert ---
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.type', 'App\Notifications\Message\NewMessagePushNotification');
    }

    #[Test]
    public function it_marks_a_single_notification_as_read(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $user->notify(new NewMessagePushNotification($this->createDummyMessage($user->organizationUsers->first()->organization_id)));

        $notification = $user->notifications()->first();
        $this->assertNull($notification->read_at);

        // --- Act ---
        $response = $this->postJson(route('notifications.mark-as-read', ['id' => $notification->id]));

        // --- Assert ---
        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    #[Test]
    public function it_marks_all_notifications_as_read(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $dummyMessage = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $user->notify(new NewMessagePushNotification($dummyMessage));
        $user->notify(new NewMessagePushNotification($dummyMessage));

        $this->assertEquals(2, $user->unreadNotifications->count());

        // --- Act ---
        $response = $this->postJson(route('notifications.mark-all-as-read'));

        // --- Assert ---
        $response->assertOk();
        $this->assertEquals(0, $user->fresh()->unreadNotifications->count());
    }

    #[Test]
    public function it_cannot_mark_notification_of_another_user_as_read(): void
    {
        // --- Arrange ---
        $user1 = User::find(1);
        $user2 = User::find(2); // Belongs to another organization
        $this->actingAs($user2);

        $dummyMessage = $this->createDummyMessage($user1->organizationUsers->first()->organization_id);
        $user1->notify(new NewMessagePushNotification($dummyMessage)); // Notification belongs to User 1

        $notification = $user1->notifications()->first();
        $this->assertNull($notification->read_at);

        // --- Act ---
        $response = $this->postJson(route('notifications.mark-as-read', ['id' => $notification->id]));

        // --- Assert ---
        $response->assertOk(); // The controller currently doesn't forbid, it just fails silently
        $this->assertNull($notification->fresh()->read_at); // The notification should remain unread
    }

    #[Test]
    public function it_clears_read_notifications(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $message1 = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $message2 = $this->createDummyMessage($user->organizationUsers->first()->organization_id);

        $user->notify(new NewMessagePushNotification($message1)); // Unread
        $user->notify(new NewMessagePushNotification($message2)); // Read

        $user->notifications()->first()->markAsRead();

        $this->assertEquals(1, $user->readNotifications->count());
        $this->assertEquals(1, $user->unreadNotifications->count());

        // --- Act ---
        $response = $this->deleteJson(route('notifications.clear-read'));

        // --- Assert ---
        $response->assertOk();
        $this->assertEquals(0, $user->fresh()->readNotifications->count());
        $this->assertEquals(1, $user->fresh()->notifications->count()); // Only unread should remain
    }

    #[Test]
    public function it_clears_all_notifications(): void
    {
        // --- Arrange ---
        $user = User::find(1);
        $this->actingAs($user);

        $message1 = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $message2 = $this->createDummyMessage($user->organizationUsers->first()->organization_id);

        $user->notify(new NewMessagePushNotification($message1)); // Unread
        $user->notify(new NewMessagePushNotification($message2)); // Read

        $user->notifications()->first()->markAsRead(); // Mark one as read

        $this->assertEquals(2, $user->notifications->count());

        // --- Act ---
        $response = $this->deleteJson(route('notifications.clear-all'));

        // --- Assert ---
        $response->assertOk();
        $this->assertEquals(0, $user->fresh()->notifications->count()); // All should be deleted
    }

    /**
     * Helper to create a valid Message object for testing notifications.
     */
    private function createDummyMessage(int $organizationId): Message
    {
        $chatbot = Chatbot::where('organization_id', $organizationId)->first();
        $chatbotChannel = $chatbot->chatbotChannels->first();

        $contact = Contact::factory()->create(['organization_id' => $organizationId]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
        ]);

        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
        ]);

        return Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'This is a dummy notification message.',
            'type' => 'incoming',
            'sender_type' => 'contact',
            'content_type' => 'text',
        ]);
    }
}
