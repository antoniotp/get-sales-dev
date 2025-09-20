<?php

namespace App\Contracts\Services\AI;

interface AIServiceInterface
{
    /**
     * Generate a response based on the conversation history and prompt.
     *
     * @param string $prompt The base prompt to guide the AI
     * @param array<array{role: string, content: string}> $history The conversation history
     * @return string The generated response
     */
    public function generateResponse(string $prompt, array $history): string;
}
