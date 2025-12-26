<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Enums\ChatbotChannel\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Chatbot;
use Inertia\Inertia;
use Inertia\Response;

class WhatsAppWebIntegrationController extends Controller
{
    public function __construct() {}

    public function index(Chatbot $chatbot): Response
    {
        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $whatsAppWebChatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $whatsAppWebChannel->id)
            ->first();

        $callRejectionMessage = null;
        if ($whatsAppWebChatbotChannel) {
            $setting = $whatsAppWebChatbotChannel->settings()
                ->where('key', SettingKey::CALL_REJECTION_MESSAGE->value)
                ->first();
            $callRejectionMessage = $setting?->value;
        }

        return Inertia::render('chatbots/integrations/whatsapp-web/index', [
            'chatbot' => $chatbot,
            'whatsAppWebChatbotChannel' => $whatsAppWebChatbotChannel ? [
                'id' => $whatsAppWebChatbotChannel->id,
                'data' => [
                    'session_id' => $whatsAppWebChatbotChannel->credentials['session_id'] ?? '',
                    'phone_number_verified_name' => $whatsAppWebChatbotChannel->credentials['phone_number_verified_name'] ?? '',
                    'display_phone_number' => $whatsAppWebChatbotChannel->credentials['display_phone_number'] ?? '',
                    'phone_number_id' => $whatsAppWebChatbotChannel->credentials['phone_number_id'] ?? '',
                    'phone_number' => $whatsAppWebChatbotChannel->credentials['phone_number'] ?? '',
                ],
                'status' => $whatsAppWebChatbotChannel->status,
            ] : null,
            'callRejectionMessage' => $callRejectionMessage,
        ]);
    }

    public function startWhatsappWebServer(Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService)
    {
        $sessionId = 'chatbot-'.$chatbot->id;

        $success = $whatsAppWebService->startSession($sessionId);

        if ($success) {
            return response()->json(['session_id' => $sessionId]);
        }

        return response()->json(['error' => 'Failed to start WhatsApp Web session.'], 500);
    }

    public function getWhatsAppWebStatus(Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService)
    {
        $status = $whatsAppWebService->getSessionStatus($chatbot);

        return response()->json($status);
    }

    public function reconnectWhatsappWebSession(Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService)
    {
        $response = $whatsAppWebService->reconnectSession($chatbot);

        if ($response['success'] !== 'error') {
            return response()->json($response);
        }

        return response()->json($response, 500);
    }
}
