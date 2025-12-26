<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Exceptions\MessageSendException;
use App\Factories\Chat\MessageSenderFactory;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendMessageToChannel implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private MessageSenderFactory $factory) {}

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        // Only process outgoing text messages
        if ($message->type !== 'outgoing' || $message->content_type !== 'text') {
            return;
        }

        try {
            $chatbotChannel = $message->conversation->chatbotChannel;
            $channelSlug = $chatbotChannel->channel->slug;

            // Get the appropriate sender service from the factory
            $sender = $this->factory->make($channelSlug);

            // Send the message and get the result
            $result = $sender->sendMessage($message);

            // If we are here, the message was sent successfully
            $message->update([
                'sent_at' => now(),
                'external_message_id' => $result->externalId,
            ]);

            event(new NewWhatsAppMessage($message));

        } catch (MessageSendException $e) {
            Log::error('Failed to send message via channel.', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            $message->update([
                'failed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            event(new NewWhatsAppMessage($message));

        } catch (Exception $e) {
            // Catch any other unexpected exceptions
            Log::critical('An unexpected error occurred while sending a message.', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message->update([
                'failed_at' => now(),
                'error_message' => 'An unexpected error occurred.',
            ]);

            event(new NewWhatsAppMessage($message));
        }
    }
}
