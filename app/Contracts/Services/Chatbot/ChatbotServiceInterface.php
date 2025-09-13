<?php

namespace App\Contracts\Services\Chatbot;

use App\Models\Organization;

interface ChatbotServiceInterface
{
    /**
     * Get all chatbots for an organization.
     *
     * @param Organization $organization
     * @param int|null $status
     * @param bool $summary
     * @return array
     */
    public function getChatbotsByOrganization( Organization $organization, ?int $status = null, bool $summary = false ): array;
}
