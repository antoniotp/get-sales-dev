<?php

namespace Tests\Feature\Jobs\AI;

use App\Contracts\Services\AI\AIServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneServiceInterface;
use App\Jobs\ProcessAIResponse;
use App\Mail\Notification\DeveloperNotification;
use App\Models\Chatbot;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ProcessAIResponse::class)]
class ProcessAIResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Log::spy();
        Queue::fake();

        $this->instance(AIServiceInterface::class, \Mockery::mock(AIServiceInterface::class));
        $this->instance(MessageServiceInterface::class, \Mockery::mock(MessageServiceInterface::class));
        $this->instance(PhoneServiceInterface::class, \Mockery::mock(PhoneServiceInterface::class));
    }

    #[Test]
    public function job_throws_exception_on_ai_service_failure(): void
    {
        // Arrange
        $user = User::find(1);
        $message = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $this->assertNotNull($message, 'No text message found. Check DatabaseSeeder.');

        // This tells PHPUnit to expect this exception. If it's not thrown, the test fails.
        $this->expectException(RequestException::class);

        // Mock the services for this specific test
        $aiServiceMock = $this->mock(AIServiceInterface::class);
        $messageServiceMock = $this->mock(MessageServiceInterface::class);
        $phoneServiceMock = $this->mock(PhoneServiceInterface::class);

        $aiServiceMock->shouldReceive('generateResponse')
            ->once()
            ->andThrow(new RequestException(new Response(new \GuzzleHttp\Psr7\Response(500))));

        $phoneServiceMock->shouldReceive('getCountryFromPhoneNumber')->andReturn('US');

        $messageServiceMock->shouldNotReceive('createAndSendOutgoingMessage');

        // Act
        $job = new ProcessAIResponse($message);
        $job->handle($aiServiceMock, $messageServiceMock, $phoneServiceMock);
    }

    #[Test]
    public function job_sends_message_on_ai_service_success(): void
    {
        // Arrange
        $user = User::find(1);
        $message = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $this->assertNotNull($message, 'No text message found. Check DatabaseSeeder.');

        $aiResponse = 'This is a successful AI response generated.';

        // Mock the services for this specific test
        $aiServiceMock = $this->mock(AIServiceInterface::class);
        $messageServiceMock = $this->mock(MessageServiceInterface::class);
        $phoneServiceMock = $this->mock(PhoneServiceInterface::class);

        $aiServiceMock->shouldReceive('generateResponse')
            ->once()
            ->andReturn($aiResponse);

        $phoneServiceMock->shouldReceive('getCountryFromPhoneNumber')->andReturn('US');

        $messageServiceMock->shouldReceive('createAndSendOutgoingMessage')
            ->once()
            ->withArgs(function ($conversation, $messageData) use ($message, $aiResponse) {
                return $conversation->id === $message->conversation->id &&
                       $messageData['content'] === $aiResponse &&
                       $messageData['content_type'] === 'text' &&
                       $messageData['sender_type'] === 'ai';
            });

        // Act
        $job = new ProcessAIResponse($message);
        $job->handle($aiServiceMock, $messageServiceMock, $phoneServiceMock);

        // Assert
        Log::shouldNotHaveReceived('error'); // Ensure no errors were logged
    }

    #[Test]
    public function job_sends_notification_on_permanent_failure(): void
    {
        // Arrange
        Mail::fake();
        $user = User::find(1);
        $message = $this->createDummyMessage($user->organizationUsers->first()->organization_id);
        $this->assertNotNull($message, 'No text message found. Check DatabaseSeeder.');

        $exception = new RequestException(new Response(new \GuzzleHttp\Psr7\Response(500)));
        $recipients = array_filter(config('notifications.ai_processing_failure.recipients'));

        if (empty($recipients)) {
            $this->fail('No developer notification recipients configured for the test.');
        }

        // Act
        $job = new ProcessAIResponse($message);
        $job->failed($exception);

        // Assert
        Mail::assertSent(DeveloperNotification::class, function ($mail) use ($recipients) {
            $emailRecipients = collect($mail->to)->map(fn ($address) => $address['address'])->all();
            sort($recipients);
            sort($emailRecipients);
            $recipientsMatch = $recipients === $emailRecipients;

            return $recipientsMatch;
        });
    }

    /**
     * Helper to create a valid Message object for testing.
     */
    private function createDummyMessage(int $organizationId): Message
    {
        $chatbot = Chatbot::where('organization_id', $organizationId)->first();
        $chatbotChannel = $chatbot->chatbotChannels->first();

        $contact = Contact::factory()->create(['organization_id' => $organizationId]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'chatbot_id' => $chatbot->id,
            'channel_id' => $chatbotChannel->channel_id,
        ]);

        $conversation = Conversation::factory()->create([
            'chatbot_channel_id' => $chatbotChannel->id,
            'contact_channel_id' => $contactChannel->id,
        ]);

        return Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'This is a dummy notification message.',
            'type' => 'incoming',
            'sender_type' => 'contact',
            'content_type' => 'text',
        ]);
    }
}
