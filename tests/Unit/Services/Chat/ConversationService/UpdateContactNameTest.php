<?php

namespace Tests\Unit\Services\Chat\ConversationService;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Services\Chat\ConversationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ConversationService::class)]
class UpdateContactNameTest extends TestCase
{
    use RefreshDatabase;

    private ConversationServiceInterface $conversationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->conversationService = $this->app->make(ConversationServiceInterface::class);
        $this->contact = Contact::factory()->create();
        $this->contactChannel = ContactChannel::factory()->create([
            'contact_id' => $this->contact->id,
            'chatbot_id' => 1, // from seeder
            'channel_id' => 1, // from seeder
        ]);
    }

    #[Test]
    public function it_can_update_a_conversations_contact_name(): void
    {
        // Arrange
        $conversation = Conversation::factory()->create([
            'contact_channel_id' => $this->contactChannel->id,
            'contact_name' => 'Old Contact',
            'assigned_user_id' => 1,
        ]);
        $newName = 'New Contact Name';

        // Act
        $result = $this->conversationService->updateContactName($conversation, $newName);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'contact_name' => $newName,
        ]);
        $conversation->refresh();
        $this->assertEquals($newName, $conversation->contact_name);
    }

    #[Test]
    public function it_returns_false_if_conversation_save_fails(): void
    {
        // Arrange
        $conversationMock = $this->mock(Conversation::class);
        $newName = 'New Contact Name';

        // Set expectations on the mock.
        // The service will set the 'contact_name' property...
        $conversationMock->shouldReceive('setAttribute')->with('contact_name', $newName);
        // ...and then it will call save(). We tell the mock to return false here.
        $conversationMock->shouldReceive('save')->once()->andReturn(false);

        // Act
        $result = $this->conversationService->updateContactName($conversationMock, $newName);

        // Assert
        $this->assertFalse($result);
    }
}
