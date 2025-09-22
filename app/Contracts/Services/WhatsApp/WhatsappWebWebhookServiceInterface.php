<?php

namespace App\Contracts\Services\WhatsApp;

interface WhatsappWebWebhookServiceInterface
{
    /**
     * Handle incoming webhook events from the WhatsApp Web service.
     *
     * @param array $data Validated data from the request.
     * @return void
     */
    public function handle(array $data): void;
}
