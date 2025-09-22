<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\WhatsappWebhookRequest;

class WhatsAppWebController extends Controller
{
    public function __construct(private readonly WhatsappWebWebhookServiceInterface $webhookService)
    {
    }

    public function handle(WhatsappWebhookRequest $request)
    {
        $this->webhookService->handle($request->validated());

        return response()->json(['status' => 'received']);
    }
}
