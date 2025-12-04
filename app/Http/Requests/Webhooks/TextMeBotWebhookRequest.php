<?php

namespace App\Http\Requests\Webhooks;

use App\Models\ChatbotChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TextMeBotWebhookRequest extends FormRequest
{
    private ?ChatbotChannel $chatbotChannel = null;

    /**
     * Determine if the user is authorized to make this request.
     * We authorize the request if we can find a corresponding chatbot channel.
     */
    public function authorize(): bool
    {
        return (bool) $this->getChatbotChannel();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:text'],
            'from' => ['required', 'string'],
            'from_name' => ['required', 'string'],
            'to' => ['required', 'string'],
            'message' => ['required', 'string'],
            'from_lid' => ['nullable', 'string'],
            'origin' => ['nullable', 'string'],
        ];
    }

    /**
     * Finds and caches the chatbot channel associated with the request.
     */
    public function getChatbotChannel(): ?ChatbotChannel
    {
        if ($this->chatbotChannel) {
            return $this->chatbotChannel;
        }

        $this->chatbotChannel = ChatbotChannel::where('credentials->phone_number', $this->input('to'))
            ->orWhere('credentials->phone_number_id', $this->input('to'))
            ->first();

        return $this->chatbotChannel;
    }
}
