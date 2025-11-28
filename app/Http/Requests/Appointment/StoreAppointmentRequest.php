<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $organizationId = $this->route('chatbot')->organization_id;

        return [
            'contact_id' => [
                'nullable',
                'integer',
                Rule::exists('contacts', 'id')->where('organization_id', $organizationId),
            ],
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => [
                'required_without:contact_id',
                'nullable',
                'string',
                'max:255',

            ],
            'appointment_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:appointment_at'],
            'remind_at' => ['nullable', 'date'],
            'chatbot_channel_id' => [
                'required',
                'integer',
                Rule::exists('chatbot_channels', 'id')->where('chatbot_id', $this->route('chatbot')->id),
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert chatbot_channel_id to integer if it's a string from the frontend
        if (isset($this->chatbot_channel_id) && is_string($this->chatbot_channel_id)) {
            $this->merge([
                'chatbot_channel_id' => (int) $this->chatbot_channel_id,
            ]);
        }
        // Convert contact_id to integer if it's a string from the frontend
        if (isset($this->contact_id) && is_string($this->contact_id)) {
            $this->merge([
                'contact_id' => (int) $this->contact_id,
            ]);
        }
    }
}
