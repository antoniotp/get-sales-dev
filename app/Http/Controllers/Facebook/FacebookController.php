<?php

namespace App\Http\Controllers\Facebook;

use App\Contracts\Services\WhatsApp\FacebookServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacebookController extends Controller
{
    public function __construct(
        private readonly FacebookServiceInterface $facebookService,
        private Organization $organization
    ) {
    }

    public function handleCallback(Request $request, Chatbot $chatbot): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $result = $this->facebookService->exchangeCodeForAccessToken($validated['code']);

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 400);
        }

        if (empty($result['waba_id'])) {
            return response()->json(['message' => 'WhatsApp Business Account ID not found.'], 400);
        }

        $subscriptionResult = $this->facebookService->subscribeToWebhooks($result['waba_id'], $result['access_token']);

        if (isset($subscriptionResult['error'])) {
            return response()->json(['message' => $subscriptionResult['error']], 400);
        }

        $phone_number_id = $request->input('phone_number_id', '');
        Log::info('phone_number_id: ' . $phone_number_id);
        $phoneInfo = [
            'display_phone_number' => '',
            'verified_name' => '',
        ];
        $pin = '';
        if ( !empty($phone_number_id) ) {
            $phoneInfo = $this->facebookService->getPhoneNumberInfo($result['access_token'], $phone_number_id);

            if (isset($phoneInfo['error'])) {
                return response()->json(['message' => $phoneInfo['error']['message'] ?? 'Failed to get phone number details.'], 400);
            }

            $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $registrationResult = $this->facebookService->registerPhoneNumber($result['access_token'], $phone_number_id, $pin);

            if (isset($registrationResult['error'])) {
                return response()->json(['message' => $registrationResult['error']], 400);
            }
        }

        if ( $chatbot->organization_id != $this->organization->id ) {
            abort(403, 'Unauthorized');
        }

        $chatbotId = $chatbot->id;

        $credentials = [
            'api_key'                        => '', //ignore
            'phone_number'                   => $phoneInfo['display_phone_number'],
            'phone_number_id'                => $phone_number_id,
            'phone_number_verified_name'     => $phoneInfo['verified_name'],
            'display_phone_number'           => $phoneInfo['display_phone_number'],
            'phone_number_access_token'      => $result['access_token'],
            'whatsapp_business_account_id'   => $result['waba_id'],
            'whatsapp_business_access_token' => $result['access_token'],
            'two_factor_pin'                 => $pin,
        ];

        ChatbotChannel::query()->updateOrCreate(
            [
                'chatbot_id' => $chatbotId,
                'channel_id' => 1, //WhatsApp Channel ID, see channels table
            ],
            [
                'name' => $chatbot->name . ' - ' . 'WhatsApp',
                'webhook_url' => 'https://graph.facebook.com/v23.0/',
                'credentials' => $credentials,
                'webhook_config' => '',
                'status' => 1,
                'last_activity_at' => now(),
            ]
        );

        return response()->json(['message' => 'Facebook account connected successfully.']);
    }
}
