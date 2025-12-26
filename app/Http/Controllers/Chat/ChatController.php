<?php

namespace App\Http\Controllers\Chat;

use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\DataTransferObjects\Chat\ConversationData;
use App\DataTransferObjects\Chat\MessageData;
use App\Events\MessageSent;
use App\Events\NewWhatsAppMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreChatRequest;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(
        private readonly Organization $organization,
        private readonly ConversationServiceInterface $conversationService
    ) {}

    public function index(Request $request, Chatbot $chatbot, ?Conversation $conversation = null): RedirectResponse|Response
    {
        $chatbotId = $chatbot->id;

        $chatbotChannels = ChatbotChannel::where('chatbot_id', $chatbotId)->with('channel')->get();
        if ($chatbotChannels->isEmpty()) {
            return redirect()
                ->route('chatbots.integrations', [$chatbotId])
                ->with('warning', 'You must connect your chatbot to a messaging channel before you can start chatting.');
        }

        $user = $request->user();

        $conversations = $this->conversationService->getConversationsForChatbot($chatbot, $user)
            ->map(fn (Conversation $c) => ConversationData::fromConversation($c)->toArray());

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

        $transformedChannels = $chatbotChannels->map(function ($chatbotChannel) {
            return [
                'id' => $chatbotChannel->id,
                'name' => $chatbotChannel->channel->name,
                'phone_number' => $chatbotChannel->credentials['phone_number'] ?? null,
            ];
        });

        $selectedConversation = null;
        if ($conversation && $conversation->chatbotChannel->chatbot_id === $chatbot->id) {
            $selectedConversation = ConversationData::fromConversation($conversation)->toArray();
        }

        return Inertia::render('chat/chat', [
            'chats' => $conversations,
            'chatbotChannels' => $transformedChannels,
            'agents' => $agents,
            'canAssign' => $canAssign,
            'selectedConversation' => $selectedConversation,
        ]);
    }

    public function store(StoreChatRequest $request, Chatbot $chatbot, ConversationServiceInterface $conversationService): JsonResponse
    {
        if ($chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validated();

        $conversation = $conversationService->startHumanConversation(
            $chatbot,
            $validatedData, // contains contact_id or phone_number (for new contact)
            $validatedData['chatbot_channel_id'],
            auth()->id()
        );

        return response()->json(
            ConversationData::fromConversation($conversation)->toArray(),
            201
        );
    }

    public function getMessages(Conversation $conversation, Request $request): JsonResponse
    {
        if ($conversation->chatbotChannel->chatbot->organization_id !== $this->organization->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with(['senderUser'])
            ->whereNotLike('content_type', 'pending')
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

    public function startFromLink(Request $request, Chatbot $chatbot, $phone_number): RedirectResponse
    {
        $request->validate([
            'cc_id' => [
                'required',
                'integer',
                Rule::exists('chatbot_channels', 'id')->where(function ($query) use ($chatbot) {
                    $query->where('chatbot_id', $chatbot->id);
                }),
            ],
        ]);
        try {
            $conversation = $this->conversationService->startConversationFromLink(
                $request->user(),
                $chatbot,
                $phone_number,
                $request->query('text'),
                $request->query('cc_id') // chatbot channel id
            );

            return redirect()->route('chats', [
                'chatbot' => $chatbot,
                'conversation' => $conversation,
            ])->with('success', 'Chat started from link.');

        } catch (AuthorizationException $e) {
            return redirect()->route('chats', [
                'chatbot' => $chatbot,
            ])->with('error', 'You are not authorized to access this conversation.');
        } catch (ModelNotFoundException $e) {
            return redirect()->route('chats', [
                'chatbot' => $chatbot,
            ])->with('error', 'The specified channel was not found.');
        }
    }

    public function retryMessage(Message $message): JsonResponse
    {
        // Authorization: Ensure the message belongs to the user's organization.
        // The policy will check if the user can update the conversation the message belongs to.
        $this->authorize('update', $message->conversation);

        // We only retry messages that have actually failed.
        if (! $message->hasFailed()) {
            return response()->json(['error' => 'Message has not failed.'], 400);
        }

        // 1. Reset the failure status.
        $message->update([
            'failed_at' => null,
            'error_message' => null,
        ]);

        // 2. Notify the frontend to show the "sending" (clock) icon again.
        event(new NewWhatsAppMessage($message));

        // 3. Put the message back into the sending queue.
        event(new MessageSent($message));

        return response()->json(['success' => true, 'message' => 'Message is being retried.']);
    }
}
