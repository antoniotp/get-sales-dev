<?php

namespace Tests\Feature\Chat\List;

use App\Enums\Chatbot\AgentVisibility;
use App\Http\Controllers\Chat\ChatController;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;

#[CoversClass(ChatController::class)]
#[CoversMethod(ChatController::class, 'index')]
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

    public function test_it_forbids_access_to_chatbot_from_another_organization(): void
    {
        // Arrange: Get user from Org 1 and chatbot from Org 2
        $userFromOrg1 = User::find(1);
        $chatbotFromOrg2 = Chatbot::find(3); // As per our seeder

        // Act
        $response = $this->actingAs($userFromOrg1)->get(route('chats', [
            'chatbot' => $chatbotFromOrg2,
        ]));

        // Assert
        $response->assertForbidden();
    }

    public function test_agent_sees_only_assigned_conversations_when_visibility_is_restricted(): void
    {
        // Arrange
        $agentUser = User::where('email', 'agent@example.com')->first();
        $restrictedChatbot = Chatbot::where('agent_visibility', AgentVisibility::ASSIGNED_ONLY)->first();

        // Create two conversations for this chatbot
        $assignedConversation = Conversation::factory()->for($restrictedChatbot->chatbotChannels->first())->create([
            'assigned_user_id' => $agentUser->id,
        ]);
        $unassignedConversation = Conversation::factory()->for($restrictedChatbot->chatbotChannels->first())->create([
            'assigned_user_id' => null,
        ]);

        // Act
        $response = $this->actingAs($agentUser)->get(route('chats', [
            'chatbot' => $restrictedChatbot,
        ]));

        // Assert
        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($assignedConversation, $unassignedConversation) {
            $page->component('chat/chat')
                ->has('chats', 1) // Should only see 1 conversation
                ->where('chats.0.id', $assignedConversation->id); // And it's the correct one

            // Extra check to be sure the unassigned one is not present
            $chats = $page->toArray()['props']['chats'];
            $this->assertFalse(
                collect($chats)->contains('id', $unassignedConversation->id),
                "The unassigned conversation was found in the 'chats' prop."
            );

            return true;
        });
    }
}
