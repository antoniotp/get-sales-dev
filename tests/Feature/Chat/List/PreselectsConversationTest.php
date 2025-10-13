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

class PreselectsConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::find(1);
        $this->chatbot = Chatbot::find(1);

        // Create a contact and conversation for the test
        $contact = Contact::factory()->create(['organization_id' => $this->chatbot->organization_id]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => 1, // Assuming channel 1 exists from seeder
        ]);
        $this->conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
        ]);
    }

    public function test_it_preselects_a_conversation_when_passed_in_url(): void
    {
        // Act
        $response = $this->actingAs($this->user)->get(route('chats', [
            'chatbot' => $this->chatbot,
            'conversation' => $this->conversation,
        ]));

        // Assert
        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('chat/chat')
            ->has('selectedConversation')
            ->where('selectedConversation.id', $this->conversation->id)
            ->has('chats')
        );
    }
}
