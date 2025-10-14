<?php

namespace Tests\Unit\Services\Chat\ConversationService;

use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;

#[CoversClass(ConversationService::class)]
#[CoversMethod(ConversationService::class, 'startConversationFromLink')]
class StartFromLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    private MockInterface $normalizerMock;

    private ConversationService $conversationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::find(1);
        $this->chatbot = Chatbot::find(1);

        $this->normalizerMock = $this->mock(PhoneNumberNormalizerInterface::class);
        $this->conversationService = new ConversationService($this->normalizerMock);
    }

    public function test_it_creates_new_contact_and_conversation(): void
    {
        // Arrange
        $phoneNumber = '15559876543';
        $normalizedNumber = '15559876543';
        $text = 'Initial message';
        $chatbotChannel = $this->chatbot->chatbotChannels->first();

        $this->normalizerMock->shouldReceive('normalize')
            ->once()
            ->with($phoneNumber)
            ->andReturn($normalizedNumber);

        // Act
        $result = $this->conversationService->startConversationFromLink(
            $this->user,
            $this->chatbot,
            $phoneNumber,
            $text,
            $chatbotChannel->id
        );

        // Assert
        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $this->chatbot->organization_id,
            'phone_number' => $normalizedNumber,
        ]);
        $this->assertDatabaseHas('conversations', [
            'id' => $result->id,
            'chatbot_channel_id' => $chatbotChannel->id,
        ]);
    }
}
