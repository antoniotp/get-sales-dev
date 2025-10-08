<?php

namespace App\Contracts\Services\Util;

interface PhoneNumberNormalizerInterface
{
    /**
     * Normalizes a phone number to a standard format for internal use.
     *
     * @param string $phoneNumber The phone number to normalize.
     * @return string The normalized phone number.
     */
    public function normalize(string $phoneNumber): string;
}
