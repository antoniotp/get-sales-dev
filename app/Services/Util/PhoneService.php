<?php

namespace App\Services\Util;

use App\Contracts\Services\Util\PhoneServiceInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class PhoneService implements PhoneServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCountryFromPhoneNumber(string $phoneNumber): string
    {
        $trimmedNumber = trim($phoneNumber);

        if (empty($trimmedNumber)) {
            return '';
        }

        $numberToParse = $trimmedNumber;
        if ($numberToParse[0] !== '+') {
            // check if it contains at least a few digits
            if (preg_match('/\d{4,}/', $numberToParse) === 0) {
                return ''; // Not a plausible phone number
            }
            $numberToParse = '+'.$numberToParse;
        }

        try {
            return (new PhoneNumber($numberToParse))->lenient()->getCountry() ?? '';
        } catch (Exception $e) {
            Log::error('Error parsing phone number: '.$e->getMessage());

            return '';
        }
    }
}
