<?php

namespace App\DataTransferObjects\Chatbot;

use App\Models\Chatbot;
use Illuminate\Contracts\Support\Arrayable;

class ChatbotSummaryData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromChatbot(Chatbot $chatbot): self
    {
        return new self(
            id: $chatbot->id,
            name: $chatbot->name,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
