<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Contracts\Services\WhatsAppServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage
{
    /**
     * Create the event listener.
     */
    public function __construct(private WhatsAppServiceInterface $whatsAppService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->message;
            $conversation = $message->conversation;
            $chatbotChannel = $conversation->chatbotChannel;

            // Only process if it's an outgoing text message and the channel is WhatsApp
            if ($message->type !== 'outgoing' ||
                $message->content_type !== 'text' ||
                $chatbotChannel->channel->slug !== 'whatsapp') {
                return;
            }

            // Get the recipient's phone number
            $recipientPhone = $conversation->contact_phone;
            if (empty($recipientPhone)) {
                throw new \Exception('Recipient phone number not found for conversation: ' . $conversation->id);
            }

            // Send the message via the specific WhatsApp channel
            $this->whatsAppService->sendMessage(
                $chatbotChannel,
                $recipientPhone,
                $message->content
            );

        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message: ' . $e->getMessage(), [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'channel_id' => $chatbotChannel->id ?? null
            ]);
        }

    }
}
