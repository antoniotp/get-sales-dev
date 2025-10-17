<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LegacyWhatsAppWebService implements WhatsAppWebServiceInterface
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

    public function getSessionStatus( Chatbot $chatbot ): array
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

    /**
     * Triggers a reconnection for a given chatbot's WhatsApp Web session.
     *
     * @param Chatbot $chatbot
     * @return array
     */
    public function reconnectSession( Chatbot $chatbot ): array
    {
        if ( empty( $this->wwebjs_url ) ) {
            Log::error( 'WhatsApp Web Service URL is not configured for reconnection.' );
            return [ 'success' => false, 'message' => 'Service not configured.' ];
        }

        $whatsAppWebChannel = Channel::where( 'slug', 'whatsapp-web' )->first();

        if ( !$whatsAppWebChannel ) {
            Log::error( 'WhatsApp Web channel not found in database for reconnection.' );
            return [ 'success' => false, 'message' => 'WhatsApp Web channel not configured.' ];
        }

        $chatbotChannel = $chatbot->chatbotChannels()
            ->where( 'channel_id', $whatsAppWebChannel->id )
            ->first();

        if ( !$chatbotChannel || !isset( $chatbotChannel->credentials['session_id'] ) ) {
            Log::warning(
                'No active WhatsApp Web session found for chatbot to reconnect.',
                [ 'chatbot_id' => $chatbot->id ]
            );
            return [ 'success' => false, 'message' => 'No active session found.' ];
        }

        $sessionId = $chatbotChannel->credentials['session_id'];

        try {
            $response = Http::withHeaders( [
                'Content-Type' => 'application/json',
            ] )->post( "{$this->wwebjs_url}/sessions/{$sessionId}/reconnect" );

            if ( $response->successful() ) {
                $nodeServiceResponse = $response->json();
                Log::info('Reconnect command sent successfully to Node.js service.', ['session_id' => $sessionId, 'response' => $nodeServiceResponse]);

                // El servicio de Node.js devuelve 'Reconnection started' o 'Session already active'
                if (isset($nodeServiceResponse['message']) && $nodeServiceResponse['message'] === 'Reconnection started') {
                    // Si la reconexión se inició, establecer el estado a CONNECTING para esperar eventos
                    $chatbotChannel->update(['status' => ChatbotChannel::STATUS_CONNECTING]);
                    return ['success' => true, 'message' => 'Reconnection initiated. Waiting for events.'];
                } elseif (isset($nodeServiceResponse['message']) && $nodeServiceResponse['message'] === 'Session already active') {
                    // Si la sesión ya está activa, actualizar el estado a CONNECTED
                    $chatbotChannel->update(['status' => ChatbotChannel::STATUS_CONNECTED]);
                    return ['success' => true, 'message' => 'Session already active.'];
                }
                // Manejar otras respuestas exitosas si las hay, o por defecto a éxito genérico
                return ['success' => true, 'message' => $nodeServiceResponse['message'] ?? 'Reconnect command sent.'];

            } else {
                Log::error('Node.js service failed to reconnect session.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                // El servicio de Node.js podría devolver 409 para 'Cannot reconnect from status'
                if ($response->status() === 409) {
                    return ['success' => false, 'message' => $response->json('message', 'Cannot reconnect from current status.')];
                }
                return ['success' => false, 'message' => 'Failed to reconnect session.'];
            }
        } catch ( Exception $e ) {
            Log::error( 'Error communicating with Node.js service for reconnection.', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ] );
            return [ 'success' => false, 'message' => 'Error communicating with service.' ];
        }
    }

    public function sendMessage(Message $message): void
    {
        if (empty($this->wwebjs_url)) {
            Log::error('WhatsApp Web Service URL is not configured.');
            // We don't throw an exception here to avoid stopping a queue
            return;
        }

        try {
            $chatbotChannel = $message->conversation->chatbotChannel;
            $sessionId = $chatbotChannel->credentials['session_id'] ?? null;
            $recipient = $message->conversation->contact_phone;
            $text = $message->content;

            if (!$sessionId || !$recipient) {
                throw new Exception('Missing session_id or recipient phone number.');
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->wwebjs_url}/sessions/{$sessionId}/messages", [
                'to' => $recipient,
                'text' => $text,
            ]);

            $response->throw(); // Throw an exception for 4xx/5xx responses

            Log::info('Message sent successfully via wwebjs.', [
                'message_id' => $message->id,
                'session_id' => $sessionId
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send message via wwebjs.', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
