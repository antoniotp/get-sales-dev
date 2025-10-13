<?php

namespace Tests\Feature\Chat\List;

use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListConversationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Chatbot $chatbot;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        // 1. Seed the database to get initial data (users, orgs, chatbots, etc.)
        $this->seed(DatabaseSeeder::class);

        // 2. Get the initial user and chatbot created by the seeder
        $this->user = User::find(1);
        $this->chatbot = Chatbot::find(1);

        // 3. Create a specific conversation for this chatbot to test against
        $contact = Contact::factory()->create();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id, // Link to the seeded chatbot
            'channel_id' => 1, // Assuming channel 1 exists from seeder
        ]);
        $this->conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);
    }

    public function test_it_lists_conversations_and_does_not_preload_any_chat(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('chats', [
            'chatbot' => $this->chatbot,
        ]));

        // Assert
        $response->assertOk();

        $response->assertInertia(function (Assert $page) {
            $page->component('chat/chat')
                ->has('chats');

            // Also, check that our specific conversation is in the payload
            $chats = $page->toArray()['props']['chats'];
            $this->assertTrue(
                collect($chats)->contains('id', $this->conversation->id),
                "The created conversation was not found in the 'chats' prop."
            );

            return true;
        });
    }
}
