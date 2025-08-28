<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
        ]);
    }
}
