<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Chatbot;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as InertiaAssert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(HandleInertiaRequests::class)]
class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // Define a dummy route for testing purposes that uses the middleware
        Route::middleware(['web', 'auth', \App\Http\Middleware\HandleInertiaRequests::class])
            ->get('/_test/inertia-props', function () {
                return Inertia::render('home');
            });

        Route::middleware(['web', 'auth', \App\Http\Middleware\HandleInertiaRequests::class])
            ->get('/_test/inertia-props-with-chatbot/{chatbot}', function (Chatbot $chatbot) {
                return Inertia::render('home');
            });
    }

    #[Test]
    public function it_shares_chatbot_from_route_parameter_when_present(): void
    {
        // Arrange
        $user = User::find(1);
        $chatbot = Chatbot::find(1);
        $this->actingAs($user);

        // Act
        $response = $this->get("/_test/inertia-props-with-chatbot/{$chatbot->id}");

        // Assert
        $response->assertOk();
        $response->assertInertia(function (InertiaAssert $page) use ($chatbot) {
            $page->component('home')
                ->has('chatbot')
                ->where('chatbot.id', $chatbot->id);
        });
    }

    #[Test]
    public function it_shares_chatbot_from_session_when_route_parameter_is_absent(): void
    {
        // Arrange
        $user = User::find(1);
        $chatbot = Chatbot::find(1);
        $this->actingAs($user);

        // Act
        $response = $this->withSession(['chatbot_id' => $chatbot->id])
            ->get('/_test/inertia-props');

        // Assert
        $response->assertOk();
        $response->assertInertia(function (InertiaAssert $page) use ($chatbot) {
            $page->component('home')
                ->has('chatbot')
                ->where('chatbot.id', $chatbot->id);
        });
    }

    #[Test]
    public function it_shares_null_chatbot_and_clears_session_if_session_chatbot_is_invalid(): void
    {
        // Arrange
        $user = User::find(1);
        $chatbotFromOtherOrg = Chatbot::find(3); // Belongs to Org 2
        $this->actingAs($user);

        // Act
        $response = $this->withSession(['chatbot_id' => $chatbotFromOtherOrg->id])
            ->get('/_test/inertia-props');

        // Assert
        $response->assertOk();
        $response->assertInertia(function (InertiaAssert $page) {
            $page->component('home')
                ->where('chatbot', null);
        });

        $this->assertFalse(session()->has('chatbot_id'));
    }

    #[Test]
    public function it_shares_null_chatbot_when_no_route_or_session_data_exists(): void
    {
        // Arrange
        $user = User::find(1);
        $this->actingAs($user);

        // Act
        $response = $this->get('/_test/inertia-props');

        // Assert
        $response->assertOk();
        $response->assertInertia(function (InertiaAssert $page) {
            $page->component('home')
                ->where('chatbot', null);
        });
    }

    #[Test]
    public function it_stores_chatbot_from_route_parameter_in_session(): void
    {
        // Arrange
        $user = User::find(1);
        $chatbot = Chatbot::find(1);
        $this->actingAs($user);

        // Ensure session is initially empty or different
        session()->forget('chatbot_id');
        $this->assertFalse(session()->has('chatbot_id'));

        // Act
        $response = $this->get("/_test/inertia-props-with-chatbot/{$chatbot->id}");

        // Assert
        $response->assertOk();
        $this->assertEquals($chatbot->id, session('chatbot_id'));
    }
}
