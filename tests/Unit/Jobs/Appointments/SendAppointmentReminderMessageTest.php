<?php

namespace Tests\Unit\Jobs\Appointments;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Jobs\Appointments\SendAppointmentReminderMessage;
use App\Models\Appointment;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Organization;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendAppointmentReminderMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function job_uses_services_to_send_reminder(): void
    {
        // 1. Arrange
        $organization = Organization::first();
        $chatbot = Chatbot::where('organization_id', $organization->id)->first();
        $channel = Channel::where('slug', 'whatsapp-web')->first();

        $chatbotChannel = ChatbotChannel::firstOrCreate(
            ['chatbot_id' => $chatbot->id, 'channel_id' => $channel->id],
            ['name' => 'Test Channel', 'credentials' => ['phone_number' => '1234567890'], 'status' => 1]
        );

        $userToAssign = $organization->getFirstEligibleManager();
        $this->assertNotNull($userToAssign, 'No suitable user found. Check seeders and Organization::getFirstEligibleManager().');

        $contact = Contact::factory()->create([
            'organization_id' => $organization->id,
            'first_name' => 'John',
        ]);

        $appointment = Appointment::create([
            'contact_id' => $contact->id,
            'chatbot_channel_id' => $chatbotChannel->id,
            'appointment_at' => '2025-12-25 10:30:00',
            'status' => 'scheduled',
        ]);

        // Mock the services
        $conversationServiceMock = $this->mock(ConversationServiceInterface::class);
        $messageServiceMock = $this->mock(MessageServiceInterface::class);

        // Mock a conversation to be returned by the service
        $mockConversation = new Conversation(['id' => 123]);

        // 2. Expect
        $conversationServiceMock->shouldReceive('startHumanConversation')
            ->once()
            ->withArgs(function ($argChatbot, $argData, $argChannelId, $argManagerId) use ($chatbot, $contact, $chatbotChannel, $userToAssign) {
                return $argChatbot->id === $chatbot->id &&
                       $argData['contact_id'] === $contact->id &&
                       $argChannelId === $chatbotChannel->id &&
                       $argManagerId === $userToAssign->id;
            })
            ->andReturn($mockConversation);

        $messageServiceMock->shouldReceive('createAndSendOutgoingMessage')
            ->once()
            ->withArgs(function ($argConversation, $argMessageData) use ($mockConversation, $userToAssign) {
                $appointmentTime = '25/12/2025 a las 10:30';
                $expectedContent = "Hola John, te recordamos tu cita para el día {$appointmentTime}.";

                return $argConversation->id === $mockConversation->id &&
                       $argMessageData['content'] === $expectedContent &&
                       $argMessageData['sender_type'] === 'human' &&
                       $argMessageData['sender_user_id'] === $userToAssign->id;
            });

        // 3. Act
        $job = new SendAppointmentReminderMessage($appointment);
        $job->handle($conversationServiceMock, $messageServiceMock);
    }
}
