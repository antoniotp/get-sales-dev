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
            'event_type' => [
                'prohibits:dataType', // Cannot exist if dataType exists
                'required_without:dataType',
                'string',
                'in:qr_code_received,client_ready,message_received,authenticated,message_sent,disconnected,message_ack',
            ],
            'session_id' => ['required_with:event_type', 'string'],
            'qr_code' => ['nullable', 'string'],
            'message' => ['nullable', 'array'],
            'from' => ['nullable', 'string'],
            'phone_number_id' => ['nullable', 'string'],

            'dataType' => [
                'prohibits:event_type', // Cannot exist if event_type exists
                'required_without:event_type',
                'string',
            ],
            'sessionId' => ['required_with:dataType', 'string'],
            'data' => ['nullable', 'array'],
            'data.ack' => ['required_if:dataType,message_ack', 'integer'],
            'data.message.id._serialized' => ['required_if:dataType,message_ack', 'string'],
        ];
    }
}
