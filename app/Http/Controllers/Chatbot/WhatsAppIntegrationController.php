<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use Inertia\Response;

class WhatsAppIntegrationController extends Controller
{
    public function __construct(private Organization $organization)
    {
    }

    public function index(Chatbot $chatbot, Request $request): Response
    {
        $whatsAppChannel = $chatbot->chatbotChannels()
            ->where('channel_id', 1)
            ->where('status', 1)
            ->first();
        $whatsAppWebChannel = Channel::where('slug', 'whatsapp-web')->first();
        $whatsAppWebChatbotChannel = $chatbot->chatbotChannels()
            ->where('channel_id', $whatsAppWebChannel->id)
            ->first();

        return Inertia::render('chatbots/integrations/whatsapp/index', [
            'chatbot' => $chatbot,
            'whatsAppChannel' => $whatsAppChannel ? [
                'id' => $whatsAppChannel->id,
                'data' => [
                    'display_phone_number' => $whatsAppChannel->credentials['display_phone_number'] ?? '',
                    'phone_number_id' => $whatsAppChannel->credentials['phone_number_id'] ?? '',
                ],
                'status' => $whatsAppChannel->status,
            ] : null,
            'whatsAppWebChatbotChannel' => $whatsAppWebChatbotChannel ? [
                'id' => $whatsAppWebChatbotChannel->id,
                'data' => [
                    'session_id' => $whatsAppWebChatbotChannel->credentials['session_id'] ?? '',
                    'phone_number_verified_name' => $whatsAppWebChatbotChannel->credentials['phone_number_verified_name'] ?? '',
                    'display_phone_number' => $whatsAppWebChatbotChannel->credentials['display_phone_number'] ?? '',
                    'phone_number_id' => $whatsAppWebChatbotChannel->credentials['phone_number_id'] ?? '',
                ],
                'status' => $whatsAppWebChatbotChannel->status,
            ]:null,
        ]);
    }

    public function startWhatsappWebServer(Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService)
    {
        $sessionId = 'chatbot-' . $chatbot->id;

        $success = $whatsAppWebService->startSession($sessionId);

        if ($success) {
            return response()->json(['session_id' => $sessionId]);
        }

        return response()->json(['error' => 'Failed to start WhatsApp Web session.'], 500);
    }

    public function getWhatsAppWebStatus( Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService )
    {
        $status = $whatsAppWebService->getSessionStatus( $chatbot );
        return response()->json($status);
    }

    public function reconnectWhatsappWebSession( Chatbot $chatbot, WhatsAppWebServiceInterface $whatsAppWebService )
    {
        $response = $whatsAppWebService->reconnectSession($chatbot);

        if ($response['success']) {
            return response()->json($response);
        }

        return response()->json($response, 500);
    }
}
