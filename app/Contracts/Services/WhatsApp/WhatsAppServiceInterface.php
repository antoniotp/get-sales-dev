<?php

namespace App\Contracts\Services\WhatsApp;

use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;

interface WhatsAppServiceInterface
{
    public function sendMessage(ChatbotChannel $channel, string $to, string $message): array;

    public function submitTemplateForReview(MessageTemplate $template): array;
}
