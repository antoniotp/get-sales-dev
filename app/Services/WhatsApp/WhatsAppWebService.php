<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
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
                Log::info( 'Reconnect command sent successfully to Node.js service.', [ 'session_id' => $sessionId ] );
                $chatbotChannel->update(['status' => ChatbotChannel::STATUS_CONNECTING]);
                return [ 'success' => true, 'message' => 'Reconnect command sent.' ];
            } else {
                Log::error( 'Node.js service failed to reconnect session.', [
                    'session_id' => $sessionId,
                    'status'     => $response->status(),
                    'response'   => $response->body(),
                ] );
                return [ 'success' => false, 'message' => 'Failed to reconnect session.' ];
            }
        } catch ( Exception $e ) {
            Log::error( 'Error communicating with Node.js service for reconnection.', [
                'session_id' => $sessionId,
                'error'      => $e->getMessage(),
            ] );
            return [ 'success' => false, 'message' => 'Error communicating with service.' ];
        }
    }
}
