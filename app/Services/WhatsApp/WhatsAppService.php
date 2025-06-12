<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsAppServiceInterface;
use App\Models\ChatbotChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements WhatsAppServiceInterface
{
    public function sendMessage(ChatbotChannel $channel, string $to, string $message): array
    {
        try {
            if ($channel->channel->slug !== 'whatsapp' || !$channel->status) {
                throw new \Exception('Invalid or inactive WhatsApp channel');
            }

            $credentials = $channel->credentials;
            Log::info('Sending WhatsApp message to ' . $to);
            Log::info('Message: ' . $message);
            Log::info( 'Credentials: ' . $credentials['access_token']);
            Log::info( 'Webhook url: ' . $channel->webhook_url);
            // Send a message using the channel-specific credentials
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['access_token'],
            ])->post($channel->webhook_url . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to send WhatsApp message: ' . $response->body());
            }

            Log::info('WhatsApp message sent successfully');
            // Update last activity
            $channel->update(['last_activity_at' => now()]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp message sending failed for channel ' . $channel->id . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
