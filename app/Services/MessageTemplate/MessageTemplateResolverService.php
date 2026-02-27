<?php

namespace App\Services\MessageTemplate;

use App\Contracts\Services\MessageTemplate\MessageTemplateResolverServiceInterface;
use App\Models\Contact;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Support\Arr;

class MessageTemplateResolverService implements MessageTemplateResolverServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function resolveValues(
        MessageTemplate $template,
        Contact $contact,
        array $manualValues = [],
        ?User $user = null
    ): array {
        $mappings = $template->variable_mappings ?? [];
        $headerMapping = Arr::get($mappings, 'header');
        $bodyMappings = Arr::get($mappings, 'body', []);

        $resolved = [
            'header' => null,
            'body' => [],
        ];

        // 1. Resolve Header Mapping (Single)
        if ($headerMapping) {
            $resolved['header'] = [
                'placeholder' => $headerMapping['placeholder'],
                'value' => $this->resolveSingleValue($headerMapping, $contact, $manualValues, $user, 'header'),
            ];
        }

        // 2. Resolve Body Mappings (Collection)
        foreach ($bodyMappings as $mapping) {
            $resolved['body'][] = [
                'placeholder' => $mapping['placeholder'],
                'value' => $this->resolveSingleValue($mapping, $contact, $manualValues, $user, 'body'),
            ];
        }

        return $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function render(MessageTemplate $template, array $resolvedValues): array
    {
        $rendered = [
            'header' => $template->header_content ?? '',
            'body' => $template->body_content,
            'footer' => $template->footer_content ?? '',
        ];

        // Replace Header
        if ($resolvedValues['header']) {
            $rendered['header'] = str_replace(
                $resolvedValues['header']['placeholder'],
                $resolvedValues['header']['value'],
                $rendered['header']
            );
        }

        // Replace Body
        foreach ($resolvedValues['body'] as $bodyVal) {
            $rendered['body'] = str_replace(
                $bodyVal['placeholder'],
                $bodyVal['value'],
                $rendered['body']
            );
        }

        return $rendered;
    }

    /**
     * Resolves a single mapping entry into its final string value.
     *
     * @param  string  $context  parameter to handle nested manual values ['header' => [], 'body' => []].
     */
    private function resolveSingleValue(array $mapping, Contact $contact, array $manualValues, ?User $user, string $context): string
    {
        $source = $mapping['source'];
        $placeholder = $mapping['placeholder'];
        $fallback = $mapping['fallback_value'] ?? '';

        // Case A: Manual value
        if ($source === 'manual') {
            // Remove brackets for an easier lookup if the user passed them in keys
            $cleanPlaceholder = str_replace(['{{', '}}'], '', $placeholder);

            return (string) Arr::get($manualValues, "{$context}.{$cleanPlaceholder}", $fallback);
        }

        // Case B: Database source
        $value = $this->getValueFromSource($source, $contact, $user);

        return (string) ($value ?? $fallback);
    }

    /**
     * Navigates the source path string to extract the value from models.
     */
    private function getValueFromSource(string $source, Contact $contact, ?User $user): mixed
    {
        $parts = explode('.', $source);
        $root = $parts[0]; // contact, organization, user, contact_entity

        return match ($root) {
            'contact' => $contact->getAttribute($parts[1] ?? ''),
            'organization' => $contact->organization?->getAttribute($parts[1] ?? ''),
            'user' => $user?->getAttribute($parts[1] ?? ''),
            'contact_entity' => $this->resolveEntityValue($parts, $contact),
            default => null,
        };
    }

    /**
     * Resolves values from the contact_entities and contact_attributes tables.
     * Path format: contact_entity.{entity_name}.{attribute_name}
     */
    private function resolveEntityValue(array $pathParts, Contact $contact): ?string
    {
        if (count($pathParts) < 3) {
            return null;
        }

        $entityName = $pathParts[1];
        $attributeName = $pathParts[2];

        // Find the entity for this contact by its name
        $entity = $contact->contactEntities()
            ->where('name', $entityName)
            ->first();

        if (! $entity) {
            return null;
        }

        // Find the specific attribute within that entity
        $attribute = $entity->attributes()
            ->where('attribute_name', $attributeName)
            ->first();

        return $attribute?->attribute_value;
    }
}
