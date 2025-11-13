<?php

namespace App\Jobs\Appointments;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Models\Appointment;
use App\Models\ChatbotChannelSetting;
use App\Models\MessageTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(['contact.organization', 'chatbotChannel.chatbot']);
    }

    /**
     * Execute the job.
     */
    public function handle(
        ConversationServiceInterface $conversationService,
        MessageServiceInterface $messageService
    ): void {
        $contact = $this->appointment->contact;
        $chatbotChannel = $this->appointment->chatbotChannel;
        $organization = $contact->organization;

        // 1. Find a user to attribute the message to using the centralized method
        $userToAssign = $organization->getFirstEligibleManager();

        if (! $userToAssign) {
            Log::error("No suitable user (Manager, Admin, or Owner) found for organization ID: {$organization->id}. Cannot send appointment reminder for appointment ID: {$this->appointment->id}.");
            $this->fail("No suitable user found for organization ID: {$organization->id}.");

            return;
        }

        // 2. Get the reminder message template
        $reminderTemplateSetting = ChatbotChannelSetting::where('chatbot_channel_id', $chatbotChannel->id)
            ->where('key', 'appointment_reminder_template_id')
            ->first();

        $messageTemplate = null;
        if ($reminderTemplateSetting && $reminderTemplateSetting->value) {
            $messageTemplate = MessageTemplate::find($reminderTemplateSetting->value);
        }

        $defaultMessage = 'Hola {{contact.first_name}}, te recordamos tu cita para el día {{appointment.datetime}}.';
        $templateContent = $messageTemplate ? $messageTemplate->body_content : $defaultMessage;

        // Replace placeholders in the template
        $appointmentTimeFormatted = $this->appointment->appointment_at->format('d/m/Y \a \l\a\s H:i');
        $messageContent = str_replace(
            ['{{contact.first_name}}', '{{appointment.datetime}}'],
            [$contact->first_name, $appointmentTimeFormatted],
            $templateContent
        );

        // 3. Start a human-led conversation
        $conversation = $conversationService->startHumanConversation(
            $chatbotChannel->chatbot,
            ['contact_id' => $contact->id],
            $chatbotChannel->id,
            $userToAssign->id
        );

        // 4. Prepare and send the message
        $messageData = [
            'content' => $messageContent,
            'content_type' => 'text',
            'sender_type' => 'human',
            'sender_user_id' => $userToAssign->id,
        ];

        $messageService->createAndSendOutgoingMessage($conversation, $messageData);

        Log::info("Successfully sent reminder for appointment ID: {$this->appointment->id}");
    }
}
