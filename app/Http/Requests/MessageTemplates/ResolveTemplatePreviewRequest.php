<?php

namespace App\Http\Requests\MessageTemplates;

use Illuminate\Foundation\Http\FormRequest;

class ResolveTemplatePreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
            'manual_values' => 'nullable|array',
        ];
    }
}
