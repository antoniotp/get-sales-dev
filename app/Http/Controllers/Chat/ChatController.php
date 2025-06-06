<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    private function getFakeChats(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'John Doe',
                'avatar' => 'J',
                'lastMessage' => 'Hey, how are you?',
                'lastMessageTime' => Carbon::now()->subMinutes(5)->toIso8601String(),
                'unreadCount' => 2,
            ],
            [
                'id' => 2,
                'name' => 'Diane Smith',
                'avatar' => 'D',
                'lastMessage' => 'The project is going great!',
                'lastMessageTime' => Carbon::now()->subHours(1)->toIso8601String(),
                'unreadCount' => 0,
            ],
        ];
    }

    private function getFakeMessages($chatId): array
    {
        $messages = [
            1 => [
                [
                    'id' => 1,
                    'content' => 'Hey, how are you?',
                    'sender' => 'John Doe',
                    'senderId' => 1,
                    'timestamp' => Carbon::now()->subMinutes(5)->toIso8601String(),
                ],
                [
                    'id' => 2,
                    'content' => "I'm doing great! How about you?",
                    'sender' => 'You',
                    'senderId' => 'me',
                    'timestamp' => Carbon::now()->subMinutes(4)->toIso8601String(),
                ],
            ],
            2 => [
                [
                    'id' => 1,
                    'content' => 'The project is going great!',
                    'sender' => 'Jane Smith',
                    'senderId' => 2,
                    'timestamp' => Carbon::now()->subHours(1)->toIso8601String(),
                ],
                [
                    'id' => 2,
                    'content' => 'That sounds awesome!',
                    'sender' => 'You',
                    'senderId' => 'me',
                    'timestamp' => Carbon::now()->subMinutes(55)->toIso8601String(),
                ],
            ],
        ];

        return $messages[$chatId] ?? [];
    }

    public function index(): Response
    {
        return Inertia::render('chat/chat', [
            'chats' => $this->getFakeChats(),
        ]);
    }

    public function getMessages($chatId): JsonResponse
    {
        return response()->json([
            'messages' => $this->getFakeMessages($chatId),
        ]);
    }
}
