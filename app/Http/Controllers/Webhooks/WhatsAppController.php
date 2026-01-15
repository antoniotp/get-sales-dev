<?php

namespace App\Http\Controllers\Webhooks;

use App\Contracts\Services\WhatsApp\WhatsAppWebhookHandlerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\WhatsAppVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WhatsAppWebhookHandlerServiceInterface $whatsAppWebhookHandler
    ) {}

    /**
     * Verify the webhook endpoint for WhatsApp Business API.
     */
    public function verify(WhatsAppVerificationRequest $request): Response
    {
        return response($request->getChallenge(), Response::HTTP_OK);
    }

    /**
     * Handle incoming webhook events from WhatsApp Business API.
     *
     * @throws ValidationException
     */
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        Log::info('WhatsApp Webhook Payload: '."\n".json_encode($payload));

        try {
            $this->whatsAppWebhookHandler->process($payload);

            return response()->noContent();
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->noContent(Response::HTTP_ACCEPTED);
        }
    }
}
