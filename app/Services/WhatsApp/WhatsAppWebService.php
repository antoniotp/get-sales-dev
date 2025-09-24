<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebService implements WhatsAppWebServiceInterface
{
    private string $wwebjs_url;
    public function __construct()
    {
        $this->wwebjs_url = rtrim(config('services.wwebjs_service.url'), '/');
    }

    /**
     * @inheritDoc
     */
    public function startSession( string $sessionId ): bool
    {
        if ( empty( $this->wwebjs_url) ) {
            Log::error('WhatsApp Web Service URL is not configured. Please check config/services.php and your .env file.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->wwebjs_url . '/sessions', [
                'externalId' => $sessionId,
            ]);

            if ($response->failed()) {
                Log::error('Failed to start session', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch ( Exception $e ) {
            Log::error('Error starting session', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
        Log::info('Successfully requested session start for wwebjs.', ['session_id' => $sessionId]);
        return true;
    }

    public function getSessionStatus( $chatbot ): array
    {
        if ( empty( $this->wwebjs_url) ) {
            Log::error('WhatsApp Web Service URL is not configured.');
            return ['status' => 'error', 'message' => 'Service not configured.'];
        }
        $sessionId = 'chatbot-' . $chatbot->id;
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->wwebjs_url . '/sessions/' . $sessionId);

            if ($response->serverError()) {
                Log::error('WhatsApp Web Service returned a server error.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                ]);
                return ['status' => 'ERROR', 'message' => 'Connection service failed.'];
            }
            if ($response->clientError()) {
                Log::warning('WhatsApp Web Service returned a client error, assuming disconnected.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['status' => 'DISCONNECTED'];
            }
            return ['status' => $response->json('status', 'UNKNOWN')];
        } catch ( Exception $e ) {
            Log::error('Error getting session status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
