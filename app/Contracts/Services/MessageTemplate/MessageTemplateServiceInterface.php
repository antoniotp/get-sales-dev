<?php

namespace App\Contracts\Services\MessageTemplate;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;

interface MessageTemplateServiceInterface
{
    /**
     * Create a new message template.
     *
     * @param  array  $data  Validated data from the request (including frontend specific fields).
     * @param  Chatbot  $chatbot  The chatbot to which the template belongs.
     */
    public function createTemplate(array $data, Chatbot $chatbot): MessageTemplate;

    /**
     * Update an existing message template.
     *
     * @param  MessageTemplate  $template  The message template instance to update.
     * @param  array  $data  Validated data from the request (including frontend specific fields).
     */
    public function updateTemplate(MessageTemplate $template, array $data): MessageTemplate;

    /**
     * Send a message template for review.
     *
     * @param  MessageTemplate  $template  The message template instance to send for review.
     */
    public function sendForReview(MessageTemplate $template): MessageTemplate;

    /**
     * Send a message template to a user.
     *
     * @param  MessageTemplate  $template  The message template instance to send
     */
    public function sendMessageTemplate(
        MessageTemplate $template,
        Conversation $conversation,
        array $manualValues,
        User $user
    ): Message;
}
