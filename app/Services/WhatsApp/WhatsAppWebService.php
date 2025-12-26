<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\DataTransferObjects\Chat\MessageSendResult;
use App\Exceptions\MessageSendException;
use App\Facades\WwebjsUrl;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebService implements WhatsAppWebServiceInterface
{
    private string $wwebjs_key;

    public function __construct()
    {
        $this->wwebjs_key = config('services.wwebjs_service.key');

        if (empty($this->wwebjs_key)) {
            Log::error('WhatsApp Web Service Key is not configured. Please check config/services.php and your .env file.');
            throw new Exception('WhatsApp Web Service API Key is not configured.');
        }
    }

    public function startSession(string $sessionId): bool
    {
        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/session/start/'.$sessionId;
        Log::info('Starting session via WhatsApp Web Service', ['session_id' => $sessionId, 'url' => $url]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->get($url);

            if ($response->successful()) {
                Log::info('Session start request was successful.', ['session_id' => $sessionId, 'response' => $response->json()]);

                return true;
            }

            Log::error('Failed to start session.', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;

        } catch (Exception $e) {
            Log::error('Error starting session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getSessionStatus(Chatbot $chatbot): array
    {
        $sessionId = 'chatbot-'.$chatbot->id;
        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/session/status/'.$sessionId;
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

    public function reconnectSession(Chatbot $chatbot): array
    {
        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        if (! $whatsAppWebChannel) {
            Log::error('WhatsApp Web channel not found in database for reconnection.');

            return ['success' => 'error', 'message' => 'WhatsApp Web channel not configured.'];
        }

        $chatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $whatsAppWebChannel->id)
            ->first();

        if (! $chatbotChannel || ! isset($chatbotChannel->credentials['phone_number'])) {
            Log::warning(
                'No active WhatsApp Web session found for chatbot to reconnect.',
                ['chatbot_id' => $chatbot->id]
            );

            return ['success' => 'error', 'message' => 'No active session found.'];
        }

        $sessionId = 'chatbot-'.$chatbot->id;
        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/session/restart/'.$sessionId;
        Log::info('Reconnecting session via WhatsApp Web Service', ['session_id' => $sessionId, 'url' => $url]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->get($url);

            Log::info('reconnect session response', $response->json());
            if ($response->successful()) {
                return $response->json();
            }
            Log::error('Node.js service failed to reconnect session.', [
                'session_id' => $sessionId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return ['success' => 'error', 'message' => 'Failed to reconnect session.'];
        } catch (Exception $e) {
            Log::error('Error communicating with Node.js service for reconnection.', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => 'error', 'message' => 'Error communicating with service.'];
        }
    }

    public function sendMessage(Message $message): MessageSendResult
    {
        $chatbot = $message->conversation->chatbotChannel->chatbot;
        $contactChannel = $message->conversation->contactChannel;
        $sessionId = 'chatbot-'.$chatbot->id;
        $chatId = $contactChannel->channel_identifier;

        if (! str_ends_with($chatId, '@c.us')) {
            $chatId .= '@c.us';
        }

        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/client/sendMessage/'.$sessionId;
        Log::info('Sending message via WhatsApp Web Service', [
            'session_id' => $sessionId,
            'message_id' => $message->id,
            'chat_id' => $chatId,
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->post($url, [
                'chatId' => $chatId,
                'contentType' => 'string', // For now, only support simple text
                'content' => $message->content,
            ]);

            if (! $response->successful()) {
                $errorBody = $response->body();
                Log::error('Failed to send message via WhatsApp Web Service.', [
                    'session_id' => $sessionId,
                    'message_id' => $message->id,
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new MessageSendException('Failed to send message via service. Status: '.$response->status().' Body: '.$errorBody);
            }

            $responseBody = $response->json();
            $externalId = $responseBody['message']['_data']['id']['_serialized'] ?? null;

            return new MessageSendResult($externalId);
        } catch (Exception $e) {
            Log::error('Error sending message via WhatsApp Web Service', [
                'session_id' => $sessionId,
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
            // Re-throw as a specific exception for the listener to catch
            throw new MessageSendException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getGroupChatInfo(string $sessionId, string $groupId): ?array
    {
        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/groupChat/getClassInfo/'.$sessionId;
        Log::info('Getting group chat info from WhatsApp Web Service', ['session_id' => $sessionId, 'group_id' => $groupId]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->post($url, [
                'chatId' => $groupId,
            ]);

            if ($response->successful() && $response->json('success')) {
                Log::info('Group chat info retrieved successfully', ['session_id' => $sessionId, 'group_id' => $groupId]);

                return $response->json('chat');
            }

            Log::error('Failed to get group chat info.', [
                'session_id' => $sessionId,
                'group_id' => $groupId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Error getting group chat info', [
                'session_id' => $sessionId,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function rejectCall(string $sessionId, string $callId, string $recipient, string $message): void
    {
        // TODO: This could be a single endpoint call to /client/rejectCall/:sessionId
        // which will handle both rejecting the call and sending the message. It doesn't exist yet.
        Log::info('Simulating call rejection and sending message.', [
            'session_id' => $sessionId,
            'call_id' => $callId,
        ]);

        $chatId = $recipient;
        if (! str_ends_with($chatId, '@c.us') && ! str_ends_with($chatId, '@g.us') && ! str_ends_with($chatId, '@lid')) {
            // It might be a LID address without the suffix, or a number.
            // if it contains '@', it's likely a LID. Otherwise, it's a phone number.
            $suffix = str_contains($chatId, '@') ? '' : '@c.us';
            $chatId .= $suffix;
        }

        $url = WwebjsUrl::getUrlForChatbot($sessionId).'/client/sendMessage/'.$sessionId;

        Log::info('Sending call rejection message via WhatsApp Web Service', [
            'session_id' => $sessionId,
            'recipient' => $chatId,
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->wwebjs_key,
            ])->post($url, [
                'chatId' => $chatId,
                'contentType' => 'string',
                'content' => $message,
            ]);

            if (! $response->successful()) {
                Log::error('Failed to send call rejection message.', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error sending call rejection message.', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
