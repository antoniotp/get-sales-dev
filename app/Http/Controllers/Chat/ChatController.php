<?php

namespace App\Http\Controllers\Chat;

use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        // Get current organization from session
        $organizationId = $request->session()->get('currentOrganizationId');

        if (!$organizationId) {
            abort(403, 'No organization available');
        }

        // Get the current chatbot (hardcoded for now, later from user selection)
        $chatbotId = 2;

        $chatbotChannel = ChatbotChannel::where('chatbot_id', $chatbotId)->first();

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
                    'avatar' => $conversation->contact_avatar ?? mb_substr($conversation->contact_name ?? 'U', 0, 1),
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
            'channelInfo' => $chatbotChannel->credentials,
        ]);

    }

    public function getMessages(Conversation $conversation, Request $request): JsonResponse
    {
        // Get current organization from session
        $organizationId = $request->session()->get('currentOrganizationId');

        if (!$organizationId) {
            return response()->json(['error' => 'No organization selected'], 403);
        }

        // Verify if the user has access to this conversation
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

    public function storeMessage(Conversation $conversation, Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'content' => 'required|string',
            'content_type' => 'required|in:text,image,audio,video,document,location',
        ]);

        // Create the message
        $message = $conversation->messages()->create([
            'type' => 'outgoing',
            'content' => $validated['content'],
            'content_type' => $validated['content_type'],
            'sender_type' => 'human',
            'sender_user_id' => auth()->id(), // Assuming user is authenticated
        ]);

        // Update conversation's last_message_at
        $conversation->update([
            'last_message_at' => now(),
        ]);

        // Dispatch the MessageSent event
        event(new MessageSent($message));
        event(new NewWhatsAppMessage($message));

        // Return the created message with the same format as getMessages
        return response()->json([
            'message' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender' => $message->senderUser?->name ?? 'You',
                'senderId' => $message->sender_user_id,
                'timestamp' => $message->created_at->toIso8601String(),
                'type' => $message->type,
                'contentType' => $message->content_type,
                'mediaUrl' => $message->media_url,
            ],
        ]);
    }
}
