<?php

namespace Tests\Feature\Chatbot;

use App\Http\Controllers\Chatbot\ChatbotSwitcherController;
use App\Models\Chatbot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ChatbotSwitcherController::class)]
class ChatbotSwitcherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function it_stores_selected_chatbot_in_session_on_switch(): void
    {
        // Arrange
        $user = User::find(1); // Belongs to Org 1
        $chatbot = Chatbot::find(1); // Belongs to Org 1
        $this->actingAs($user);

        // Act
        $response = $this->post(route('chatbot_switcher.switch'), [
            'new_chatbot_id' => $chatbot->id,
        ]);

        // Assert
        $response->assertRedirect(route('chats', ['chatbot' => $chatbot->id]));
        $this->assertAuthenticated();
        $this->assertEquals($chatbot->id, session('chatbot_id'));
    }

    #[Test]
    public function it_returns_403_when_switching_to_a_chatbot_from_another_organization(): void
    {
        // Arrange
        $user = User::find(1); // Belongs to Org 1
        $chatbotFromAnotherOrg = Chatbot::find(3); // Belongs to Org 2
        $this->actingAs($user);

        // Act
        $response = $this->post(route('chatbot_switcher.switch'), [
            'new_chatbot_id' => $chatbotFromAnotherOrg->id,
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertNull(session('chatbot_id'));
    }
}
