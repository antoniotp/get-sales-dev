<?php

namespace App\Http\Requests\Webhooks;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class WhatsAppVerificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->query('hub_verify_token') === config('services.whatsapp.verify_token');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'hub_mode' => ['required', 'string', 'in:subscribe'],
            'hub_verify_token' => ['required', 'string'],
            'hub_challenge' => ['required', 'string'],
        ];
    }

    /**
     * Get the challenge string from the request.
     */
    public function getChallenge(): string
    {
        return $this->query('hub_challenge');
    }
}
