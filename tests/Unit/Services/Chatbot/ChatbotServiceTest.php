<?php

namespace Tests\Unit\Services\Chatbot;

use App\Models\Chatbot;
use App\Models\Organization;
use App\Models\User;
use App\Services\Chatbot\ChatbotService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ChatbotService::class)]
class ChatbotServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatbotService $chatbotService;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the database to get consistent test data
        $this->seed(DatabaseSeeder::class);
        $this->chatbotService = new ChatbotService;
    }

    #[Test]
    public function find_for_user_returns_chatbot_if_accessible_and_active(): void
    {
        // Arrange
        $user = User::find(1); // Belongs to Org 1
        $chatbot = Chatbot::find(1); // Belongs to Org 1 and is active

        // Set the user's current organization context
        $user->last_organization_id = 1;
        $user->save();

        // Act
        $result = $this->chatbotService->findForUser($chatbot->id, $user);

        // Assert
        $this->assertInstanceOf(Chatbot::class, $result);
        $this->assertEquals($chatbot->id, $result->id);
    }

    #[Test]
    public function find_for_user_returns_null_if_chatbot_belongs_to_another_organization(): void
    {
        // Arrange
        $user = User::find(1); // Belongs to Org 1
        $chatbotFromOtherOrg = Chatbot::find(3); // Belongs to Org 2

        // Set the user's current organization context
        $user->last_organization_id = 1;
        $user->save();

        // Act
        $result = $this->chatbotService->findForUser($chatbotFromOtherOrg->id, $user);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_for_user_returns_null_if_chatbot_is_inactive(): void
    {
        // Arrange
        $user = User::find(1);
        $organization = Organization::find(1);

        // Create a specific inactive chatbot for this test, mirroring seeder creation
        $inactiveChatbot = Chatbot::create([
            'organization_id' => $organization->id,
            'name' => 'Inactive Test Bot',
            'system_prompt' => 'You are an inactive bot.',
            'status' => 0, // Inactive
        ]);

        $user->last_organization_id = $organization->id;
        $user->save();

        // Act
        $result = $this->chatbotService->findForUser($inactiveChatbot->id, $user);

        // Assert
        $this->assertNull($result);
    }
}
