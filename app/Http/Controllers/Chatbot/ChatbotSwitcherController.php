<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class ChatbotSwitcherController extends Controller
{
    public function __construct( private Organization $organization)
    {
    }

    public function list( ChatbotServiceInterface $chatbotService )
    {
        $chatbots = $chatbotService->getChatbotsByOrganization( $this->organization, 1, true );
        return response()->json( [
            'success' => true,
            'switcherChatbots' => $chatbots
        ] );
    }

    public function switch( ChatbotServiceInterface $chatbotService )
    {}
}
