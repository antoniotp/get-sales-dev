<?php

namespace App\Contracts\Services\WhatsApp;

interface FacebookServiceInterface
{
    public function exchangeCodeForAccessToken(string $code): array;

    public function getPhoneNumberInfo(string $accessToken, string $phoneNumberId): array;

    public function subscribeToWebhooks(string $wabaId, string $accessToken): array;

    public function registerPhoneNumber(string $accessToken, string $phoneNumberId, string $pin): array;
}
