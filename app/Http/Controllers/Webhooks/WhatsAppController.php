<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use App\Http\Requests\Webhooks\WhatsAppVerificationRequest;
use App\Services\WhatsApp\WebhookHandlerService;
use Illuminate\Validation\ValidationException;

class WhatsAppController extends Controller
{
    public function __construct(
        protected WebhookHandlerService $webhookHandler
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

        Log::info('WhatsApp Webhook Payload:', $payload);

        try {
            $this->webhookHandler->process( $payload );

            return response()->noContent();
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->noContent(Response::HTTP_ACCEPTED);
        }
    }
}
