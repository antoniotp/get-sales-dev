<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        // Get current organization (hardcoded for now, later from session)
        $organizationId = 1;

        // Get the current chatbot (hardcoded for now, later from user selection)
        $chatbotId = 1;

        $conversations = Conversation::query()
            ->select([
                'conversations.*',
            ])
            ->with(['latestMessage', 'chatbotChannel.chatbot'])
            ->whereHas('chatbotChannel.chatbot', function ($query) use ($organizationId, $chatbotId) {
                $query->where('chatbots.organization_id', $organizationId)
                    ->where('chatbots.id', $chatbotId);
            })
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'name' => $conversation->contact_name ?? $conversation->contact_phone,
                    'avatar' => $conversation->contact_avatar ?? substr($conversation->contact_name ?? 'U', 0, 1),
                    'lastMessage' => $conversation->latestMessage->first()?->content ?? '',
                    'lastMessageTime' => $conversation->last_message_at?->toIso8601String(),
                    'unreadCount' => $conversation->messages()
                        ->whereNull('read_at')
                        ->where('type', 'incoming')
                        ->count(),
                ];
            });

        return Inertia::render('chat/chat', [
            'chats' => $conversations,
        ]);

    }

    public function getMessages( Conversation $conversation ): JsonResponse
    {
        // Verify if the user has access to this conversation
        $organizationId = 1; // Hardcoded for now

        if (!$conversation->chatbotChannel->chatbot->where('organization_id', $organizationId)->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with(['senderUser'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => $message->sender_type === 'contact'
                        ? ($message->conversation->contact_name ?? $message->conversation->contact_phone)
                        : ($message->senderUser?->name ?? 'AI'),
                    'senderId' => $message->sender_type === 'contact'
                        ? 'contact'
                        : ($message->sender_user_id ?? 'ai'),
                    'timestamp' => $message->created_at->toIso8601String(),
                    'type' => $message->type,
                    'contentType' => $message->content_type,
                    'mediaUrl' => $message->media_url,
                ];
            });

        // Mark messages as read
        $conversation->messages()
            ->whereNull('read_at')
            ->where('type', 'incoming')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages,
        ]);

    }
}
