<?php

namespace App\Contracts\Services\Util;

interface PhoneServiceInterface
{
    /**
     * Get the country code from a phone number.
     *
     * @return string The ISO 3166-1 alpha-2 country code. Returns an empty string if the country cannot be determined.
     */
    public function getCountryFromPhoneNumber(string $phoneNumber): string;
}
