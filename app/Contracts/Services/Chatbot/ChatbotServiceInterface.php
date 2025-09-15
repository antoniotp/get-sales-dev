<?php

namespace App\Contracts\Services\Chatbot;

use App\Models\Organization;
use App\Models\User;

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
    public function getChatbotsByOrganization(
        Organization $organization,
        ?int $status = null,
        bool $summary = false
    ): array;

    public function canSwitchToChatbot( int $chatbot_id, Organization $organization, User $user): bool;
}
