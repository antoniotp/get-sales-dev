<?php

namespace App\Services\AI;

use App\Contracts\Services\AI\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTService implements AIServiceInterface
{
    public function __construct(
        private string $apiKey,
        private readonly string $model = 'gpt-5-nano'
    ) {
        $this->apiKey = config('services.openai.api_key');
        Log::debug('ChatGPT Service Initialized');
        Log::debug('Model: '.$this->model);
    }

    public function generateResponse(string $prompt, array $history): string
    {
        try {
            $messages = $this->formatMessages($prompt, $history);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('ChatGPT API error', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                $response->throw();
            }

            return $response->json('choices.0.message.content');

        } catch (\Exception $e) {
            Log::error('ChatGPT Service error', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Format messages for the ChatGPT API
     *
     * @param  array<array{role: string, content: string}>  $history
     * @return array<array{role: string, content: string}>
     */
    private function formatMessages(string $prompt, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => $prompt],
        ];
        $history = array_reverse($history);

        return array_merge($messages, $history);
    }
}
