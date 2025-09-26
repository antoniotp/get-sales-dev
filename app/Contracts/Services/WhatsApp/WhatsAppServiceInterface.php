<?php

namespace App\Contracts\Services\WhatsApp;

use App\Contracts\Services\Chat\ChannelMessageSenderInterface;
use App\Models\MessageTemplate;

interface WhatsAppServiceInterface extends ChannelMessageSenderInterface
{
    public function submitTemplateForReview(MessageTemplate $template): array;
}
