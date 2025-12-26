<?php

namespace App\Services\Util;

use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;

class PhoneNumberNormalizer implements PhoneNumberNormalizerInterface
{
    public function normalize(string $phoneNumber): string
    {
        $digits = preg_replace('/\D/', '', $phoneNumber);

        //Handle specific country code rules.

        // Argentina: Add '9' after '54' for mobile numbers.
        // The final format should have 13 digits: +54 9 {10-digit number}.
        // This handles cases where a 10-digit national number is provided without the '9'.
        if (str_starts_with($digits, '54') && strlen($digits) === 12) {
            return '549' . substr($digits, 2);
        }

        // Brazil: Add '9' after the area code for mobile numbers.
        // The final format should have 13 digits: +55 {area code} 9 {8-digit number}.
        if (str_starts_with($digits, '55') && strlen($digits) === 12) {
            // Area codes are 2 digits. The '9' is inserted after the area code.
            return substr($digits, 0, 4) . '9' . substr($digits, 4);
        }

        // Mexico: Ensure mobile numbers (52 + 10 digits) have a '1' after '52'.
        if (str_starts_with($digits, '52') && strlen($digits) === 12) {
            return '521' . substr($digits, 2);
        }

        return $digits;
    }
}
