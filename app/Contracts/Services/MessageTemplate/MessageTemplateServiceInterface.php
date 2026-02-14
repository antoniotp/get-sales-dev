<?php

namespace App\Contracts\Services\MessageTemplate; // Updated namespace

use App\Models\Chatbot;
use App\Models\MessageTemplate;

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
}
