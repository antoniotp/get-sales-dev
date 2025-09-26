<?php

namespace App\Contracts\Services\WhatsApp;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\Models\Chatbot;

/**
 * Interface for the unofficial WhatsApp Web Service (whatsapp-web.js).
 */
interface WhatsAppWebServiceInterface extends ChannelMessageSenderInterface
{
    /**
     * Requests the Node.js service to start a new WhatsApp session.
     *
     * @param string $sessionId Our internal unique identifier for the session.
     * @return bool True on success, false on failure.
     */
    public function startSession(string $sessionId): bool;
    public function getSessionStatus( Chatbot $chatbot );
    public function reconnectSession( Chatbot $chatbot );
}
