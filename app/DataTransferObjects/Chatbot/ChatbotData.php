<?php

namespace App\DataTransferObjects\Chatbot;

use App\Models\Chatbot;
use Illuminate\Contracts\Support\Arrayable;

class ChatbotData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public int $status,
        public string $createdAt,
    ) {
    }

    public static function fromChatbot(Chatbot $chatbot, bool $truncate = false): self
    {
        return new self(
            id: $chatbot->id,
            name: $chatbot->name,
            description: $truncate ? self::truncateDescription($chatbot->description) : $chatbot->description,
            status: $chatbot->status,
            createdAt: $chatbot->created_at->format('M d, Y'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->createdAt,
        ];
    }

    private static function truncateDescription(?string $description, int $limit = 120): ?string
    {
        if (!$description) {
            return null;
        }

        return strlen($description) > $limit
            ? substr($description, 0, $limit) . '...'
            : $description;
    }
}
