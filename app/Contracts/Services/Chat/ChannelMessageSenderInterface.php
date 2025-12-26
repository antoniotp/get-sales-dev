<?php

namespace App\Contracts\Services\Chat;

use App\DataTransferObjects\Chat\MessageSendResult;
use App\Models\Message;

/**
 * Defines a generic interface for sending a message through any channel.
 */
interface ChannelMessageSenderInterface
{
    /**
     * Sends a message.
     *
     * @param  Message  $message  The message to be sent.
     */
    public function sendMessage(Message $message): MessageSendResult;
}
