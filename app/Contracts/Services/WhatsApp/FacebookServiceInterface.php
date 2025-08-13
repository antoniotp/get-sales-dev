<?php

namespace App\Contracts\Services\WhatsApp;

interface FacebookServiceInterface
{
    public function exchangeCodeForAccessToken(string $code): array;

    public function getPhoneNumberInfo(string $accessToken, string $phoneNumberId): array;
}
