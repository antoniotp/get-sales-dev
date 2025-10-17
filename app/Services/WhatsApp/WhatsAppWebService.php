<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Models\Chatbot;
use App\Models\Message;
use Exception;

class WhatsAppWebService implements WhatsAppWebServiceInterface
{
    public function startSession(string $sessionId): bool
    {
        throw new Exception('Not implemented');
    }

    public function getSessionStatus(Chatbot $chatbot)
    {
        throw new Exception('Not implemented');
    }

    public function reconnectSession(Chatbot $chatbot)
    {
        throw new Exception('Not implemented');
    }

    public function sendMessage(Message $message): void
    {
        throw new Exception('Not implemented');
    }
}
