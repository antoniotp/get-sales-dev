<?php

namespace Tests\Feature\Chat;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Http\Controllers\Chat\ChatController;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ChatController::class)]
class StartFromLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::find(1);
        $this->chatbot = Chatbot::find(1);
        $this->chatbot->load('organization');
    }

    public function test_it_starts_conversation_and_redirects_to_chat_with_preselection(): void
    {
        // Arrange
        $phoneNumber = '15551234567';
        $text = 'Hello from a link';
        $chatbotChannel = $this->chatbot->chatbotChannels->first();

        // 1. Correctly create Contact and ContactChannel for the expected Conversation
        $contact = Contact::factory()->create(['organization_id' => $this->chatbot->organization_id]);
        $chatbotChannel = $this->chatbot->chatbotChannels->first();
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
            'channel_identifier' => $phoneNumber, // Link to the phone number used in the test
        ]);

        // 2. Create a mock conversation that we expect the service to return
        $expectedConversation = Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
        ]);

        // 2. Mock the ConversationService
        $mockService = $this->mock(ConversationServiceInterface::class);
        $mockService->shouldReceive('startConversationFromLink')
            ->once()
            ->withArgs(function ($user, $chatbot, $phone, $message, $channelId) use ($phoneNumber, $text, $chatbotChannel) {
                return $user->id === $this->user->id &&
                       $chatbot->id === $this->chatbot->id &&
                       $phone === $phoneNumber &&
                       $message === $text &&
                       $channelId === $chatbotChannel->id;
            })
            ->andReturn($expectedConversation);

        // 3. Define the expected redirect route
        $expectedRoute = route('chats', [
            'chatbot' => $this->chatbot,
            'conversation' => $expectedConversation,
        ]);

        // Act
        $response = $this->actingAs($this->user)->get(route('chats.start', [
            'chatbot' => $this->chatbot,
            'phone_number' => $phoneNumber,
            'text' => $text,
            'channel_id' => $chatbotChannel->id,
        ]));

        // Assert
        $response->assertRedirect($expectedRoute);
    }
}
