<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;

class WhatsappWebWebhookRouterService implements WhatsappWebWebhookServiceInterface
{
    public function __construct(
        private readonly LegacyWhatsappWebWebhookService $legacyWhatsappWebWebhookService,
        private readonly WhatsappWebWebhookService $whatsappWebWebhookService,
    ) {}

    public function handle(array $data): void
    {
        if (isset($data['dataType'])) {
            $this->whatsappWebWebhookService->handle($data);
        } else {
            $this->legacyWhatsappWebWebhookService->handle($data);
        }
    }
}
