<?php

namespace Tests\Feature\Chat;

use App\Models\Chatbot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $this->chatbot->load('organization'); // Ensure organization is loaded
    }

    public function test_start_from_link_route_exists_and_redirects(): void
    {
        // This test is expected to fail until the route is defined.
        // Arrange
        $phoneNumber = '15551234567';
        $text = 'Hello from a link';

        // Act & Assert
        try {
            $route = route('chats.start', [
                'chatbot' => $this->chatbot,
                'phone_number' => $phoneNumber,
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            $this->fail('The route \'chats.start\' has not been defined yet.');
        }

        $response = $this->actingAs($this->user)->get($route);

        // Assert
        $response->assertStatus(302); // Assert it's a redirect
    }
}
