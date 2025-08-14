<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\FacebookServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookService implements FacebookServiceInterface
{
    public function exchangeCodeForAccessToken(string $code): array
    {
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');
        Log::info('Facebook token exchange started.');
        Log::info('Client ID: ' . $clientId);
        Log::info('Client Secret: ' . $clientSecret);
        Log::info('Code: ' . $code);

        try {
            // Exchange the short-lived code for a long-lived user access token
            $response = Http::get('https://graph.facebook.com/v23.0/oauth/access_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
            ]);

            if ($response->failed()) {
                Log::error('Facebook token exchange failed.', $response->json());
                return ['error' => 'Failed to retrieve access token.'];
            }

            $accessToken = $response->json('access_token');
            Log::info('Access Token: ', [$accessToken]);

            // Get App Access Token to debug the User Access Token
            $appTokenResponse = Http::get('https://graph.facebook.com/oauth/access_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($appTokenResponse->failed()) {
                Log::error('Facebook app token retrieval failed.', $appTokenResponse->json());
                return ['error' => 'Failed to retrieve app access token.'];
            }

            $appAccessToken = $appTokenResponse->json('access_token');
            Log::info('App Access Token: ', [$appAccessToken]);

            // Debug the token to get WABA ID and other details
            $debugResponse = Http::get('https://graph.facebook.com/debug_token', [
                'input_token' => $accessToken,
                'access_token' => $appAccessToken,
            ]);

            if ($debugResponse->failed() || $debugResponse->json('data.is_valid') === false) {
                Log::error('Facebook token debug failed.', $debugResponse->json());
                return ['error' => 'Failed to debug access token.'];
            }

            $data = $debugResponse->json('data');
            Log::info('Token Debug Data:', $data);

            if (!in_array('whatsapp_business_management', $data['scopes'])) {
                 return ['error' => 'Missing whatsapp_business_management permission.'];
            }

            return [
                'access_token' => $accessToken,
                'waba_id' => $data['granular_scopes'][0]['target_ids'][0] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Error during Facebook token exchange process: ' . $e->getMessage());
            return ['error' => 'An unexpected error occurred.'];
        }
    }

    public function getPhoneNumberInfo(string $accessToken, string $phoneNumberId): array
    {
        $response = Http::get("https://graph.facebook.com/v23.0/{$phoneNumberId}", [
            'access_token' => $accessToken,
            'fields' => 'display_phone_number,verified_name',
        ]);

        return $response->json();
    }

    public function subscribeToWebhooks(string $wabaId, string $accessToken): array
    {
        Log::info('Subscribing to WABA webhooks for WABA ID: ' . $wabaId);

        $response = Http::withToken($accessToken)->post("https://graph.facebook.com/v23.0/{$wabaId}/subscribed_apps");

        if ($response->failed()) {
            Log::error('Failed to subscribe to WABA webhooks.', $response->json());
            return ['error' => 'Failed to subscribe to WABA webhooks.'];
        }

        Log::info('Successfully subscribed to WABA webhooks.', $response->json());
        return $response->json();
    }
}
