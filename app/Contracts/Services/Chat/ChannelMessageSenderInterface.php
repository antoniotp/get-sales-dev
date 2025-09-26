<?php

namespace App\Contracts\Services\Chat;

use App\Models\Message;

/**
 * Defines a generic interface for sending a message through any channel.
 */
interface ChannelMessageSenderInterface
{
    /**
     * Sends a message.
     *
     * @param Message $message The message to be sent.
     * @return void
     */
    public function sendMessage(Message $message): void;
}
