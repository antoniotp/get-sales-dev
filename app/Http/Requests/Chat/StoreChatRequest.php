<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatRequest extends FormRequest
{
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
            'message' => 'nullable|string|max:4096',
            'chatbot_channel_id' => [
                'required',
                'integer',
                Rule::exists('chatbot_channels', 'id')->where('chatbot_id', $this->route('chatbot')->id),
            ],
        ];
    }
}
