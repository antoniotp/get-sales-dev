<?php

namespace App\Http\Requests\MessageTemplates;

use App\Contracts\Services\WhatsApp\WabaLanguageServiceInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMessageTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by policies or middleware at the controller level
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(WabaLanguageServiceInterface $wabaLanguageService): array
    {
        $chatbot = $this->route('chatbot');
        $templateId = $this->route('template')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure the name is unique for the given chatbot channel, ignoring the current template
                Rule::unique('message_templates')->ignore($templateId)->where(function ($query) {
                    return $query->where('chatbot_channel_id', $this->input('chatbot_channel_id'));
                }),
            ],
            'display_name' => 'required|string|max:255',
            'chatbot_channel_id' => [
                'required',
                'exists:chatbot_channels,id',
                // Ensure the selected channel belongs to the chatbot in the route
                Rule::exists('chatbot_channels', 'id')->where(function ($query) use ($chatbot) {
                    return $query->where('chatbot_id', $chatbot->id);
                }),
            ],
            'category_id' => 'required|exists:message_template_categories,id',
            'language' => ['required', 'string', Rule::in($wabaLanguageService->getEnabledCodes())],
            'header_type' => 'required|string|in:none,text,image,video,document',
            'header_content' => 'nullable|string|max:1024',
            'header_variable' => 'nullable|array',
            'header_variable.placeholder' => 'required_with:header_variable|string',
            'header_variable.example' => 'required_with:header_variable|string',
            'header_variable_type' => 'nullable|string|in:positional,named',
            'body_content' => 'required|string|max:4096',
            'footer_content' => 'nullable|string|max:255',
            'button_config' => 'nullable|array',
            'button_config.*.type' => 'required|string|in:URL,QUICK_REPLY',
            'button_config.*.text' => 'required|string|max:255',
            'button_config.*.url' => 'required_if:button_config.*.type,URL|nullable|string|url',
            'variables_schema' => 'nullable|array',
            'variables_schema.*.placeholder' => 'required_with:variables_schema|string',
            'variables_schema.*.example' => 'required_with:variables_schema|string',
            'variable_type' => 'nullable|string|in:positional,named',
            'header_variable_mapping' => 'nullable|array',
            'header_variable_mapping.placeholder' => 'required_with:header_variable_mapping|string',
            'header_variable_mapping.source' => 'required_with:header_variable_mapping|string',
            'header_variable_mapping.label' => 'required_with:header_variable_mapping|string',
            'header_variable_mapping.fallback_value' => 'nullable|string',
            'variable_mappings' => 'nullable|array',
            'variable_mappings.*.placeholder' => 'required_with:variable_mappings|string',
            'variable_mappings.*.source' => 'required_with:variable_mappings|string',
            'variable_mappings.*.label' => 'required_with:variable_mappings|string',
            'variable_mappings.*.fallback_value' => 'nullable|string',
        ];
    }
}
