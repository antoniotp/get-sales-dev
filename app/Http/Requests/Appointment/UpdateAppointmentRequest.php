<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // The authorization logic will be handled by the AppointmentPolicy.
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
            'appointment_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after:appointment_at'],
            'remind_at' => ['nullable', 'date'],
        ];
    }
}
