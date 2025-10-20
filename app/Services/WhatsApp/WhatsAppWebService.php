<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Models\Chatbot;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebService implements WhatsAppWebServiceInterface
{
    private string $wwebjs_url;

    private string $wwebjs_key;

    public function __construct()
    {
        $this->wwebjs_url = rtrim(config('services.wwebjs_service.url'), '/');
        $this->wwebjs_key = config('services.wwebjs_service.key');

        if (empty($this->wwebjs_url) || empty($this->wwebjs_key)) {
            Log::error('WhatsApp Web Service URL or Key is not configured. Please check config/services.php and your .env file.');
            throw new Exception('WhatsApp Web Service URL or API Key is not configured.');
        }
    }

    public function startSession(string $sessionId): bool
    {
        throw new Exception('Not implemented');
    }

    public function getSessionStatus(Chatbot $chatbot): array
    {
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = $this->wwebjs_url.'/session/status/'.$sessionId;
        Log::info('Getting session status from WhatsApp Web Service', ['session_id' => $sessionId, 'url' => $url, 'key' => $this->wwebjs_key]);
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->get($url);
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
                    'body' => $response->body(),
                ]);

                return ['status' => 'DISCONNECTED'];
            }
            Log::info('Session status retrieved successfully', ['session_id' => $sessionId, 'status' => $response->json()]);
            if ($response->json('success')) {
                return ['status' => $response->json('state', 'UNKNOWN')];
            } else {
                /*
                .message = 'session_not_found'
                .message = 'session_not_connected'
                message: 'browser tab closed'
                message: 'session closed'
                 */
                return ['status' => 'DISCONNECTED', 'message' => $response->json('message')];
            }
        } catch (Exception $e) {
            Log::error('Error getting session status', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }

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
