<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppWebService implements WhatsAppWebServiceInterface
{
    private string $wwebjs_url;
    public function __construct()
    {
        $this->wwebjs_url = rtrim(config('services.wwebjs_service.url'), '/');
    }

    /**
     * @inheritDoc
     */
    public function startSession( string $sessionId ): bool
    {
        if ( empty( $this->wwebjs_url) ) {
            Log::error('WhatsApp Web Service URL is not configured. Please check config/services.php and your .env file.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->wwebjs_url . '/sessions', [
                'externalId' => $sessionId,
            ]);

            if ($response->failed()) {
                Log::error('Failed to start session', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch ( Exception $e ) {
            Log::error('Error starting session', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
        Log::info('Successfully requested session start for wwebjs.', ['session_id' => $sessionId]);
        return true;
    }
}
