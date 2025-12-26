<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\PotentiallyTranslatedString;
use ReCaptcha\ReCaptcha as GoogleReCaptcha;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            $fail('The reCAPTCHA verification is required.');

            return;
        }

        $recaptcha = new GoogleReCaptcha(config('services.recaptcha.secret_key'));

        $response = $recaptcha->setExpectedHostname(request()->getHost())
            ->setScoreThreshold(0.5)
            ->verify($value, request()->ip());

        if (! $response->isSuccess()) {
            Log::error('reCAPTCHA validation failed: '.implode(', ', $response->getErrorCodes()));
            $fail('The reCAPTCHA verification failed.');
        }
    }
}
