<?php

namespace App\Services\Chatbot;

use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\DataTransferObjects\Chatbot\ChatbotData;
use App\DataTransferObjects\Chatbot\ChatbotSummaryData;
use App\Models\Chatbot;
use App\Models\Organization;

class ChatbotService implements ChatbotServiceInterface
{

    /**
     * @inheritDoc
     */
    public function getChatbotsByOrganization( Organization $organization, ?int $status = null, bool $summary = false ): array
    {
        $query = $organization->chatbots();

        // Filter by status if provided
        if ( $status ) {
            $query->where('status', $status);
        }

        // Select fields based on summary flag
        $fields = $summary ? ['id', 'name'] : ['id', 'name', 'description', 'status', 'created_at'];
        $query->select($fields);

        $chatbots = $query->orderBy('created_at', 'desc')
            ->get();

        // If summary, map to the summary DTO, otherwise use the full DTO
        if ($summary) {
            return $chatbots->map(fn(Chatbot $chatbot) => ChatbotSummaryData::fromChatbot($chatbot)->toArray())->toArray();
        }

        return $chatbots->map(fn(Chatbot $chatbot) => ChatbotData::fromChatbot($chatbot, true)->toArray())->toArray();
    }
}
