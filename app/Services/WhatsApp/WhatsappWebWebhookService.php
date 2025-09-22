<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Events\WhatsApp\WhatsappQrCodeReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappWebWebhookService implements WhatsappWebWebhookServiceInterface
{
    public function handle(array $data): void
    {
        $eventType = $data['event_type'];

        $methodName = 'handle' . Str::studly($eventType);

        if (method_exists($this, $methodName)) {
            $this->$methodName($data);
        } else {
            Log::warning("No handler for WhatsApp Web webhook event: {$eventType}", $data);
        }
    }

    /**
     * Handles the 'qr' event.
     * This event is triggered when a QR code is generated for authentication.
     */
    private function handleQr(array $data): void
    {
        Log::info('Handling QR code event', ['session_id' => $data['session_id']]);

        if (!empty($data['qr'])) {
            WhatsappQrCodeReceived::dispatch($data['session_id'], $data['qr']);
        } else {
            Log::warning('QR code event received without QR code data.', ['session_id' => $data['session_id']]);
        }
    }

    /**
     * Handles the 'authenticated' event.
     * This event is triggered when the WhatsApp client successfully authenticates.
     */
    private function handleAuthenticated(array $data): void
    {
        Log::info('Handling authenticated event', ['session_id' => $data['session_id']]);
        // Future logic: Update the channel status to 'connected' in the database.
    }

    /**
     * Handles the 'message' event.
     * This event is triggered when a new message is received.
     */
    private function handleMessage(array $data): void
    {
        Log::info('Handling message event', ['session_id' => $data['session_id']]);
        // Future logic: Process the incoming message and store it.
    }

    /**
     * Handles the 'ready' event.
     * This event is triggered when the WhatsApp client is ready to send and receive messages.
     */
    private function handleReady(array $data): void
    {
        Log::info('Handling ready event', ['session_id' => $data['session_id']]);
        // Future logic: Update the channel status to 'ready' or 'active'.
    }

    /**
     * Handles the 'disconnected' event.
     * This event is triggered when the WhatsApp client is disconnected.
     */
    private function handleDisconnected(array $data): void
    {
        Log::info('Handling disconnected event', ['session_id' => $data['session_id']]);
        // Future logic: Update the channel status to 'disconnected'.
    }
}
