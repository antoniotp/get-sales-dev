<?php

namespace App\Contracts\Services\WhatsApp;

/**
 * Interface for the unofficial WhatsApp Web Service (whatsapp-web.js).
 */
interface WhatsAppWebServiceInterface
{
    /**
     * Requests the Node.js service to start a new WhatsApp session.
     *
     * @param string $sessionId Our internal unique identifier for the session.
     * @return bool True on success, false on failure.
     */
    public function startSession(string $sessionId): bool;
}
