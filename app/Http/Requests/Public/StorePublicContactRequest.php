<?php

namespace App\Http\Requests\Public;

use App\Models\PublicFormLink;
use App\Rules\Recaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public forms are always authorized
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        /** @var PublicFormLink $formLink */
        $formLink = $this->route('formLink');
        $formLink->load('publicFormTemplate');
        $organizationId = $formLink->chatbot->organization_id;

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('contacts')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'country_code' => ['required', 'string'],
            'language_code' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'honeypot_field' => ['prohibited'],
        ];

        if (app()->environment() !== 'testing') {
            $rules['g-recaptcha-response'] = ['required', new Recaptcha];
        }

        if (empty($formLink->publicFormTemplate->custom_fields_schema)) {
            return $rules;
        }

        // Add dynamic rules from custom_fields_schema
        foreach ($formLink->publicFormTemplate->custom_fields_schema as $field) {
            $fieldName = 'custom_fields.'.$field['name'];
            $fieldRules = $field['validation'] ?? [];

            if (! empty($fieldRules)) {
                $rules[$fieldName] = $fieldRules;
            }
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        /** @var PublicFormLink $formLink */
        $formLink = $this->route('formLink');
        $formLink->load('publicFormTemplate');

        $attributes = [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'email' => 'correo electrónico',
            'phone_number' => 'teléfono para WhatsApp',
            'country_code' => 'país',
        ];

        if (empty($formLink->publicFormTemplate->custom_fields_schema)) {
            return $attributes;
        }

        foreach ($formLink->publicFormTemplate->custom_fields_schema as $field) {
            $attributes['custom_fields.'.$field['name']] = strtolower($field['label']);
        }

        return $attributes;
    }
}
