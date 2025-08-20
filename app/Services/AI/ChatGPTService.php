<?php

namespace App\Services\AI;

use App\Contracts\Services\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTService implements AIServiceInterface
{
    public function __construct(
        private string $apiKey,
        private readonly string $model = 'gpt-5-nano'
    ) {
        $this->apiKey = config('services.openai.api_key');
        Log::info('ChatGPT Service Initialized');
        Log::info('Model: ' . $this->model);
    }

    public function generateResponse(string $prompt, array $history): string
    {
        try {
            $messages = $this->formatMessages($prompt, $history);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('ChatGPT API error', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                return "I'm sorry, I couldn't generate a response right now.";
            }

            return $response->json('choices.0.message.content');

        } catch (\Exception $e) {
            Log::error('ChatGPT Service error', [
                'error' => $e->getMessage()
            ]);
            return "I'm sorry, an error occurred while processing your message.";
        }
    }

    /**
     * Format messages for the ChatGPT API
     *
     * @param string $prompt
     * @param array<array{role: string, content: string}> $history
     * @return array<array{role: string, content: string}>
     */
    private function formatMessages(string $prompt, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => $prompt]
        ];

        return array_merge($messages, $history);
    }
}
