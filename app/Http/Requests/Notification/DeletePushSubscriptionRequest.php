<?php

namespace App\Http\Requests\Notification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeletePushSubscriptionRequest extends FormRequest
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
        return [
            'endpoint' => ['required', 'string', 'max:500', Rule::exists('push_subscriptions', 'endpoint')->where(function ($query) {
                $query->where('subscribable_id', $this->user()->id)
                    ->where('subscribable_type', get_class($this->user()));
            })],
        ];
    }
}
