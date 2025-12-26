<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\TextMeBotWebhookRequest;

class TextMeBotWebhookController extends Controller
{
    public function __construct(private readonly WhatsappWebWebhookServiceInterface $webhookService) {}

    public function handle(TextMeBotWebhookRequest $request)
    {
        $chatbotChannel = $request->getChatbotChannel();
        $originalMessage = $request->validated();

        $transformedData = [
            'dataType' => 'message',
            'sessionId' => 'chatbot-'.$chatbotChannel->chatbot_id,
            'data' => [
                'message' => [
                    'id' => [
                        '_serialized' => uniqid('textmebot-').'@c.us',
                    ],
                    'from' => $originalMessage['from'],
                    'to' => $originalMessage['to'],
                    'body' => $originalMessage['message'],
                    'type' => 'chat', // 'text' from source is mapped to 'chat'
                    'timestamp' => time(),
                    'hasMedia' => false,
                    'fromMe' => false,
                    'notifyName' => $originalMessage['from_name'],
                    'author' => $originalMessage['from_lid'] ?? null,
                    '_data' => [
                        'notifyName' => $originalMessage['from_name'],
                    ],
                ],
            ],
        ];

        $this->webhookService->handle($transformedData);

        return response()->json(['status' => 'received']);
    }
}
