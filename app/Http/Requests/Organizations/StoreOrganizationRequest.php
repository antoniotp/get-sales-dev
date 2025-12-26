<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class StoreOrganizationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:255',
            'locale' => 'nullable|string|in:EN,ES,FR,IT,PT',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $slug = Str::slug($this->input('name'));
            if (Organization::where('slug', $slug)->exists()) {
                $validator->errors()->add('name', 'The organization name has already been taken. Please choose another one.');
            }
        });
    }
}
