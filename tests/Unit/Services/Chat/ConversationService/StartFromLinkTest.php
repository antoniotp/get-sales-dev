<?php

namespace Tests\Unit\Services\Chat\ConversationService;

use App\Contracts\Services\Chat\ConversationAuthorizationServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Enums\Chatbot\AgentVisibility;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Role;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
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

    private MockInterface $authServiceMock;

    private ConversationService $conversationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::find(1);
        $this->chatbot = Chatbot::find(1);

        $this->normalizerMock = $this->mock(PhoneNumberNormalizerInterface::class);
        $this->authServiceMock = $this->mock(ConversationAuthorizationServiceInterface::class);

        // Default behavior: most tests don't deal with this, so we assume the user is not a restricted agent.
        $this->authServiceMock->shouldReceive('isAgentSubjectToVisibilityRules')->andReturn(false)->byDefault();

        $this->conversationService = new ConversationService($this->normalizerMock, $this->authServiceMock);
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

    public function test_it_assigns_new_conversation_when_agent_visibility_is_assigned_only(): void
    {
        // Arrange
        $this->chatbot->agent_visibility = AgentVisibility::ASSIGNED_ONLY;
        $this->chatbot->save();

        $phoneNumber = '15551112233'; // New number
        $normalizedNumber = '15551112233';
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
        $this->assertTrue($result->wasRecentlyCreated);
        $this->assertEquals($this->user->id, $result->assigned_user_id);
        $this->assertDatabaseHas('conversations', [
            'id' => $result->id,
            'assigned_user_id' => $this->user->id,
        ]);
    }

    public function test_it_throws_authorization_exception_for_existing_assigned_conversation_with_different_user(): void
    {
        // Arrange
        $this->chatbot->agent_visibility = AgentVisibility::ASSIGNED_ONLY;
        $this->chatbot->save();

        // The user the conversation is assigned to
        $assignedUser = User::factory()->create();
        $agentRole = Role::where('slug', 'agent')->firstOrFail();
        $assignedUser->organizations()->attach($this->chatbot->organization_id, [
            'role_id' => $agentRole->id,
        ]);

        // The 'agent' user who will try to access the conversation
        $accessingUser = User::factory()->create();
        $accessingUser->organizations()->attach($this->chatbot->organization_id, [
            'role_id' => $agentRole->id,
        ]);

        $phoneNumber = '15557778899';
        $normalizedNumber = '15557778899';
        $chatbotChannel = $this->chatbot->chatbotChannels->first();

        $contact = Contact::factory()->create([
            'organization_id' => $this->chatbot->organization_id,
            'phone_number' => $normalizedNumber,
        ]);

        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $this->chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
            'channel_identifier' => $normalizedNumber,
        ]);

        // Manually create an existing conversation assigned to another user
        Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
            'external_conversation_id' => $normalizedNumber,
            'assigned_user_id' => $assignedUser->id,
        ]);

        $this->normalizerMock->shouldReceive('normalize')
            ->once()
            ->with($phoneNumber)
            ->andReturn($normalizedNumber);

        // This test specifically checks a restricted agent, so we override the default mock behavior.
        $this->authServiceMock->shouldReceive('isAgentSubjectToVisibilityRules')
            ->once()
            ->andReturn(true);

        // Assert
        $this->expectException(AuthorizationException::class);

        // Act
        $this->conversationService->startConversationFromLink(
            $accessingUser, // An agent user, different from the one assigned
            $this->chatbot,
            $phoneNumber,
            'some text',
            $chatbotChannel->id
        );
    }

    public function test_it_allows_admin_user_to_access_existing_assigned_conversation(): void
    {
        // Arrange
        $this->chatbot->agent_visibility = AgentVisibility::ASSIGNED_ONLY;
        $this->chatbot->save();

        // An agent the conversation is assigned to
        $assignedUser = User::factory()->create();
        $agentRole = Role::where('slug', 'agent')->firstOrFail();
        $assignedUser->organizations()->attach($this->chatbot->organization_id, ['role_id' => $agentRole->id]);

        // An admin user who will try to access the conversation
        $accessingAdmin = User::factory()->create();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $accessingAdmin->organizations()->attach($this->chatbot->organization_id, ['role_id' => $adminRole->id]);

        $phoneNumber = '15557778899';
        $normalizedNumber = '15557778899';
        $chatbotChannel = $this->chatbot->chatbotChannels->first();

        $contact = Contact::factory()->create(['organization_id' => $this->chatbot->organization_id, 'phone_number' => $normalizedNumber]);
        $contactChannel = ContactChannel::factory()->create(['contact_id' => $contact->id, 'chatbot_id' => $this->chatbot->id, 'channel_id' => $chatbotChannel->channel_id, 'channel_identifier' => $normalizedNumber]);
        $conversation = Conversation::factory()->create(['chatbot_channel_id' => $chatbotChannel->id, 'contact_channel_id' => $contactChannel->id, 'external_conversation_id' => $normalizedNumber, 'assigned_user_id' => $assignedUser->id]);

        $this->normalizerMock->shouldReceive('normalize')->once()->with($phoneNumber)->andReturn($normalizedNumber);

        // Mock the authorization check to return false for the admin user
        $this->authServiceMock->shouldReceive('isAgentSubjectToVisibilityRules')
            ->with($accessingAdmin, \Mockery::any()) // Be specific about the user
            ->once()
            ->andReturn(false);

        // Act
        $result = $this->conversationService->startConversationFromLink(
            $accessingAdmin,
            $this->chatbot,
            $phoneNumber,
            'some text',
            $chatbotChannel->id
        );

        // Assert
        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertEquals($conversation->id, $result->id);
    }
}
