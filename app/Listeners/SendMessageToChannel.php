<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Factories\Chat\MessageSenderFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class SendMessageToChannel implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private MessageSenderFactory $factory)
    {
    }

    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        $message = $event->message;

        try {
            // Only process outgoing text messages
            if ($message->type !== 'outgoing' || $message->content_type !== 'text') {
                return;
            }

            $chatbotChannel = $message->conversation->chatbotChannel;
            $channelSlug = $chatbotChannel->channel->slug;

            // Get the appropriate sender service from the factory
            $sender = $this->factory->make($channelSlug);

            // Send the message
            $sender->sendMessage($message);

        } catch (Exception $e) {
            Log::error('Failed to send message via channel.', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'error' => $e->getMessage(),
            ]);

            // re-throw the exception to retry
            // throw $e;
        }
    }
}
