<?php

namespace App\Http\Controllers\Chatbot;

use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function switch( ChatbotServiceInterface $chatbotService, Request $request )
    {
        $request->validate([
            'new_chatbot_id' => ['required', 'exists:chatbots,id'],
        ]);

        $success = $chatbotService->canSwitchToChatbot( $request->new_chatbot_id, $this->organization, auth()->user() );

        if (!$success) {
            return response()->json([
                'message' => 'You do not have access to this chatbot.'
            ], 403);
        }

        return Inertia::location(route('chats', [ 'chatbot' => $request->new_chatbot_id ]));
    }
}
