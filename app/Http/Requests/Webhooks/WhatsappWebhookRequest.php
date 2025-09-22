<?php

namespace App\Http\Requests\Webhooks;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WhatsappWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, we'll authorize all webhook requests.
        // We can add signature verification later if needed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string'],
            'session_id' => ['required', 'string'],
            'qr' => ['nullable', 'string'],
            'message' => ['nullable', 'array'],
        ];
    }
}
