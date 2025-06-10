<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class WebhookHandlerService
{
    /**
     * Process the incoming webhook payload.
     *
     * @param array<string, mixed> $payload
     */
    public function process(array $payload): void
    {
        if (!isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            return;
        }

        $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];

        match ($message['type']) {
            'text' => $this->handleTextMessage($message),
            'image' => $this->handleImageMessage($message),
            'document' => $this->handleDocumentMessage($message),
            default => $this->handleUnsupportedMessage($message),
        };
    }

    /**
     * Handle a text message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleTextMessage(array $message): void
    {
        // Implementar lógica para mensajes de texto
    }

    /**
     * Handle image message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleImageMessage(array $message): void
    {
        // Implementar lógica para imágenes
    }

    /**
     * Handle document message type.
     *
     * @param array<string, mixed> $message
     */
    private function handleDocumentMessage(array $message): void
    {
        // Implementar lógica para documentos
    }

    /**
     * Handle unsupported message types.
     *
     * @param array<string, mixed> $message
     */
    private function handleUnsupportedMessage(array $message): void
    {
        Log::info('Unsupported message type received', $message);
    }
}
