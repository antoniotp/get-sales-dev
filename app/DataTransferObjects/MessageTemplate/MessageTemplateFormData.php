<?php

namespace App\DataTransferObjects\MessageTemplate;

use App\Models\MessageTemplate;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MessageTemplateFormData implements Arrayable
{
    public function __construct(
        public ?int $id,
        public string $display_name,
        public string $name,
        public int $category_id,
        public int $chatbot_channel_id,
        public string $language,
        public string $header_type,
        public ?string $header_content,
        public ?array $header_variable, // { placeholder: string, example: string }
        public ?string $header_variable_type, // 'positional' | 'named'
        public string $body_content,
        public ?string $footer_content,
        public ?array $button_config, // Array of ButtonConfig
        public ?array $variables_schema, // Array of { placeholder: string, example: string }
        public ?string $variable_type, // 'positional' | 'named'
    ) {}

    public static function fromMessageTemplate(MessageTemplate $template): self
    {
        $headerVariable = null;
        $headerVariableType = null;
        $variablesSchema = null;
        $variableType = null;

        // Reconstruct header_variable and header_variable_type from example_data
        $exampleData = $template->example_data ?? [];

        // --- Header Variable Reconstruction ---
        if ($template->header_type === 'text') {
            $headerTextExamples = Arr::get($exampleData, 'header_text');
            $headerTextNamedExamples = Arr::get($exampleData, 'header_text_named_params');

            // Regex to find the first variable in header_content
            preg_match('/\{\{(\w+)}}/', $template->header_content, $matches);
            $headerPlaceholder = $matches[0] ?? null;

            if ($headerPlaceholder) {
                if (! empty($headerTextNamedExamples)) {
                    // Named variable
                    $headerVariableType = 'named';
                    $paramName = Str::after(Str::before($headerPlaceholder, '}}'), '{{');
                    $exampleValue = Arr::first($headerTextNamedExamples, fn ($item) => $item['param_name'] === $paramName)['example'] ?? '';
                    $headerVariable = [
                        'placeholder' => $headerPlaceholder,
                        'example' => $exampleValue,
                    ];
                } elseif (! empty($headerTextExamples)) {
                    // Positional variable
                    $headerVariableType = 'positional';
                    $exampleValue = Arr::first($headerTextExamples) ?? '';
                    $headerVariable = [
                        'placeholder' => $headerPlaceholder,
                        'example' => $exampleValue,
                    ];
                }
            }
        }

        // --- Body Variables Reconstruction ---
        $bodyTextExamples = Arr::get($exampleData, 'body_text');
        $bodyTextNamedExamples = Arr::get($exampleData, 'body_text_named_params');

        // Extract placeholders from body_content
        preg_match_all('/\{\{(\w+)}}/', $template->body_content, $bodyPlaceholdersMatches);
        $bodyPlaceholders = $bodyPlaceholdersMatches[0] ?? [];

        if (! empty($bodyPlaceholders)) {
            $currentVariablesSchema = [];
            if (! empty($bodyTextNamedExamples)) {
                $variableType = 'named';
                foreach ($bodyPlaceholders as $placeholder) {
                    $paramName = Str::after(Str::before($placeholder, '}}'), '{{');
                    $example = Arr::first($bodyTextNamedExamples, fn ($item) => $item['param_name'] === $paramName)['example'] ?? '';
                    $currentVariablesSchema[] = compact('placeholder', 'example');
                }
            } elseif (! empty($bodyTextExamples)) {
                $variableType = 'positional';
                $examples = Arr::first($bodyTextExamples) ?? []; // body_text is an array of arrays [["ex1", "ex2"]]
                foreach ($bodyPlaceholders as $index => $placeholder) {
                    $example = $examples[$index] ?? '';
                    $currentVariablesSchema[] = compact('placeholder', 'example');
                }
            }
            $variablesSchema = ! empty($currentVariablesSchema) ? $currentVariablesSchema : null;
        }

        return new self(
            id: $template->id,
            display_name: $template->display_name ?? $template->name,
            name: $template->name,
            category_id: $template->category_id,
            chatbot_channel_id: $template->chatbot_channel_id,
            language: $template->language,
            header_type: $template->header_type,
            header_content: $template->header_content,
            header_variable: $headerVariable,
            header_variable_type: $headerVariableType,
            body_content: $template->body_content,
            footer_content: $template->footer_content,
            button_config: $template->button_config,
            variables_schema: $variablesSchema,
            variable_type: $variableType,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'chatbot_channel_id' => $this->chatbot_channel_id,
            'language' => $this->language,
            'header_type' => $this->header_type,
            'header_content' => $this->header_content,
            'header_variable' => $this->header_variable,
            'header_variable_type' => $this->header_variable_type,
            'body_content' => $this->body_content,
            'footer_content' => $this->footer_content,
            'button_config' => $this->button_config,
            'variables_schema' => $this->variables_schema,
            'variable_type' => $this->variable_type,
        ];
    }
}
