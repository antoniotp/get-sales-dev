<?php

namespace App\Contracts\Services\WhatsApp;

interface WhatsAppWebhookHandlerServiceInterface
{
    /**
     * Process the incoming webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void;
}
