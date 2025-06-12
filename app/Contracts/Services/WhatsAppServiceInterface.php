<?php

namespace App\Contracts\Services;

use App\Models\ChatbotChannel;

interface WhatsAppServiceInterface
{
    public function sendMessage(ChatbotChannel $channel, string $to, string $message): array;
}
