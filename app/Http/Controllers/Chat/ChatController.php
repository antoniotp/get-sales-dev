<?php

namespace App\Http\Controllers\Chat;

use App\Contracts\Services\Chat\MessageServiceInterface;
use App\DataTransferObjects\Chat\ConversationData;
use App\DataTransferObjects\Chat\MessageData;
use App\Enums\Chatbot\AgentVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(private Organization $organization) {}

    public function store(StoreChatRequest $request, Chatbot $chatbot, ConversationService $conversationService): JsonResponse
    {
        if ($chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validated();

        $conversation = $conversationService->startHumanConversation(
            $chatbot,
            $validatedData, // contains contact_id or phone_number (for new contact)
            $validatedData['chatbot_channel_id']
        );

        return response()->json(
            ConversationData::fromConversation($conversation)->toArray(),
            201
        );
    }

    public function index(Request $request, Chatbot $chatbot): RedirectResponse|Response
    {
        $chatbotId = $chatbot->id;

        $chatbotChannels = ChatbotChannel::where('chatbot_id', $chatbotId)->with('channel')->get();
        if ($chatbotChannels->isEmpty()) {
            return redirect()
                ->route('chatbots.integrations', [$chatbotId])
                ->with('warning', 'You must connect your chatbot to a messaging channel before you can start chatting.');
        }

        $user = $request->user();

        $conversationsQuery = Conversation::query()
            ->select([
                'conversations.*',
            ])
            ->with(['latestMessage', 'chatbotChannel.chatbot', 'assignedUser'])
            ->whereHas('chatbotChannel.chatbot', function ($query) use ($chatbotId) {
                $query->where('chatbots.organization_id', $this->organization->id)
                    ->where('chatbots.id', $chatbotId);
            });

        // Apply agent visibility filter
        $role = $user->getRoleInOrganization($this->organization);
        if ($role && $role->slug === 'agent' && $chatbot->agent_visibility === AgentVisibility::ASSIGNED_ONLY) {
            $conversationsQuery->where('conversations.assigned_user_id', $user->id);
        }

        $conversations = $conversationsQuery->orderBy('last_message_at', 'desc')
            ->get()
            ->map(fn (Conversation $conversation) => ConversationData::fromConversation($conversation)->toArray());

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

        $transformedChannels = $chatbotChannels->map(function ($chatbotChannel) {
            return [
                'id' => $chatbotChannel->id,
                'name' => $chatbotChannel->channel->name,
                'phone_number' => $chatbotChannel->credentials['phone_number'] ?? null,
            ];
        });

        return Inertia::render('chat/chat', [
            'chats' => $conversations,
            'chatbotChannels' => $transformedChannels,
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
            ->map(fn (Message $message) => MessageData::fromMessage($message)->toArray());

        // Mark messages as read
        $conversation->messages()
            ->whereNull('read_at')
            ->where('type', 'incoming')
            ->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function storeMessage(Conversation $conversation, Request $request, MessageServiceInterface $messageService): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'content' => 'required|string',
            'content_type' => 'required|in:text,image,audio,video,document,location',
        ]);

        $messageData = [
            'content' => $validated['content'],
            'content_type' => $validated['content_type'],
            'sender_type' => 'human',
            'sender_user_id' => auth()->id(),
        ];

        $message = $messageService->createAndSendOutgoingMessage($conversation, $messageData);

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
