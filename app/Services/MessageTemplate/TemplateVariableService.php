<?php

namespace App\Services\MessageTemplate;

use App\Contracts\Services\MessageTemplate\TemplateVariableServiceInterface;
use App\Models\Chatbot;
use App\Models\ChatbotContactSchema;

class TemplateVariableService implements TemplateVariableServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAvailableVariables(Chatbot $chatbot): array
    {
        $systemVariables = $this->getSystemVariables();
        $dynamicVariables = $this->getDynamicVariablesForChatbot($chatbot);

        return array_merge($systemVariables, $dynamicVariables);
    }

    /**
     * Gets the hard-coded system variables.
     */
    private function getSystemVariables(): array
    {
        $variables = [];
        $systemVariablesConfig = [
            'contact' => [
                'first_name' => 'Contacto: Nombre',
                'last_name' => 'Contacto: Apellido',
                'email' => 'Contacto: Email',
                'phone_number' => 'Contacto: Número de Teléfono',
            ],
            'organization' => [
                'name' => 'Organización: Nombre',
            ],
            // another 'user' related variable could be added here if needed
        ];

        foreach ($systemVariablesConfig as $object => $fields) {
            foreach ($fields as $fieldName => $label) {
                $sourcePath = "{$object}.{$fieldName}";
                $variables[] = [
                    'label' => $label, // label to show to users
                    'source_path' => $sourcePath, // dot notation path to save in variable_mapping
                    'placeholder_name' => str_replace('.', '_', $sourcePath), // placeholder for meta: contact_first_name
                ];
            }
        }

        return $variables;
    }

    /**
     * Gets dynamic variables from the schemas defined for a specific chatbot.
     */
    private function getDynamicVariablesForChatbot(Chatbot $chatbot): array
    {
        $schemas = ChatbotContactSchema::where('chatbot_id', $chatbot->id)->get();
        $dynamicVariables = [];

        foreach ($schemas as $schema) {
            // We only process variables that belong to a defined entity
            if (empty($schema->entity_type)) {
                continue;
            }

            foreach ($schema->schema_definition as $field) {
                $sourcePath = "contact_entity.{$schema->entity_type}.{$field['name']}";

                // Use source path as the key to prevent duplicates
                $dynamicVariables[$sourcePath] = [
                    'label' => $field['label'],
                    'source_path' => $sourcePath,
                    'placeholder_name' => str_replace(['.', ' '], '_', strtolower($sourcePath)),
                ];
            }
        }

        return array_values($dynamicVariables);
    }
}
