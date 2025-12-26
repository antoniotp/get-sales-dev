<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Or add authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
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
            if ($this->has('name')) {
                $slug = Str::slug($this->input('name'));
                $organizationId = $this->route('organization')->id;

                if (Organization::where('slug', $slug)->where('id', '!=', $organizationId)->exists()) {
                    $validator->errors()->add('name', 'The organization name has already been taken. Please choose another one.');
                }
            }
        });
    }
}
