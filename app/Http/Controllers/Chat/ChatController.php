<?php

namespace App\Http\Controllers\Chat;

use App\DataTransferObjects\Chat\ConversationData;
use App\DataTransferObjects\Chat\MessageData;
use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(private Organization $organization)
    {
    }

    public function index(Request $request, Chatbot $chatbot): Response
    {
        $chatbotId = $chatbot->id;

        //TODO: the following must be dynamic depending on messaging channel
        $chatbotChannel = ChatbotChannel::where('chatbot_id', $chatbotId)->first();

        $conversations = Conversation::query()
            ->select([
                'conversations.*',
            ])
            ->with(['latestMessage', 'chatbotChannel.chatbot', 'assignedUser'])
            ->whereHas('chatbotChannel.chatbot', function ($query) use ($chatbotId) {
                $query->where('chatbots.organization_id', $this->organization->id)
                    ->where('chatbots.id', $chatbotId);
            })
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn(Conversation $conversation) => ConversationData::fromConversation($conversation)->toArray());

        $user = $request->user();
        $role = $user->getRoleInOrganization($this->organization);
        $canAssign = $role && $role->level > 40;

        $agents = [];
        if ($canAssign) {
            $agents = User::query()
                ->whereHas('organizationUsers', function ($query) {
                    $query->where('organization_id', $this->organization->id)
                        ->whereHas('role', fn ($q) => $q->where('level', '>=', 40));
                })
                ->select('id', 'name')
                ->get();
        }

        return Inertia::render('chat/chat', [
            'chats' => $conversations,
            'channelInfo' => $chatbotChannel->credentials,
            'agents' => $agents,
            'canAssign' => $canAssign,
        ]);

    }

    public function getMessages(Conversation $conversation, Request $request): JsonResponse
    {
        if ($conversation->chatbotChannel->chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with(['senderUser'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn(Message $message) => MessageData::fromMessage($message)->toArray());

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
            'message' => MessageData::fromMessage($message)->toArray(),
        ]);
    }

    public function updateConversationMode(Conversation $conversation, Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'mode' => 'required|in:ai,human',
        ]);

        if ($conversation->chatbotChannel->chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Update conversation mode
        $conversation->update([
            'mode' => $validated['mode'],
        ]);

        return response()->json([
            'success' => true,
            'mode' => $conversation->mode,
        ]);
    }

    }