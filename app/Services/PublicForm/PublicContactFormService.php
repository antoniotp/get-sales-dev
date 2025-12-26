<?php

namespace App\Services\PublicForm;

use App\Contracts\Services\PublicForm\PublicContactFormServiceInterface;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\PublicFormLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PublicContactFormService implements PublicContactFormServiceInterface
{
    public function register(PublicFormLink $formLink, array $validatedData): Contact
    {
        try {
            return DB::transaction(function () use ($formLink, $validatedData) {
                // 1. Create or update the Contact
                $contact = Contact::updateOrCreate(
                    [
                        'organization_id' => $formLink->chatbot->organization_id,
                        'email' => $validatedData['email'],
                    ],
                    [
                        'first_name' => $validatedData['first_name'],
                        'last_name' => $validatedData['last_name'],
                        'phone_number' => $validatedData['phone_number'],
                        'country_code' => $validatedData['country_code'] ?? null,
                        'language_code' => $validatedData['language_code'] ?? null,
                        'timezone' => $validatedData['timezone'] ?? null,
                    ]
                );

                // 2. Create a ContactEntity if configured in the template
                $formTemplate = $formLink->publicFormTemplate;
                $entityConfig = $formTemplate->entity_config ?? [];
                $entity = null;

                if ($entityConfig['creates_entity'] ?? false) {
                    $entityType = $entityConfig['entity_type'] ?? 'default';
                    $entityNameField = $entityConfig['entity_name_field'] ?? null;

                    $entityName = $entityNameField
                        ? ($validatedData['custom_fields'][$entityNameField] ?? null)
                        : null;

                    $entity = $contact->contactEntities()->create([
                        'type' => $entityType,
                        'name' => $entityName,
                    ]);
                }

                // 3. Create the ContactChannel link
                if ($formLink->channel_id) {
                    $contact->contactChannels()->updateOrCreate(
                        [
                            'chatbot_id' => $formLink->chatbot_id,
                            'channel_id' => $formLink->channel_id,
                            'channel_identifier' => $validatedData['phone_number'],
                        ]
                    );
                }

                // 4. Handle appointment creation
                $customFields = $validatedData['custom_fields'] ?? [];
                if (isset($customFields['appointment_datetime'])) {
                    // Find the correct chatbot_channel_id
                    $chatbotChannel = ChatbotChannel::where('chatbot_id', $formLink->chatbot_id)
                        ->where('channel_id', $formLink->channel_id)
                        ->first();

                    if ($chatbotChannel) {
                        $contact->appointments()->create([
                            'chatbot_channel_id' => $chatbotChannel->id,
                            'appointment_at' => $customFields['appointment_datetime'],
                        ]);
                    }
                    // Remove appointment field to prevent it from being saved as an attribute
                    unset($customFields['appointment_datetime']);
                }

                // 5. Create/update custom attributes for the new entity
                if ($entity && ! empty($customFields)) {
                    foreach ($customFields as $name => $value) {
                        // Find the field schema to check its type
                        $fieldSchema = collect($formLink->publicFormTemplate->custom_fields_schema)
                            ->firstWhere('name', $name);

                        // Handle checkbox boolean value
                        if ($fieldSchema && $fieldSchema['type'] === 'checkbox') {
                            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        }

                        // Associate attribute with the new entity
                        $entity->attributes()->updateOrCreate(
                            ['attribute_name' => $name],
                            ['attribute_value' => $value]
                        );
                    }
                }

                return $contact;
            });
        } catch (Throwable $e) {
            Log::error('Error registering contact from public form: '.$e->getMessage(), [
                'exception' => $e,
                'form_link_uuid' => $formLink->uuid,
            ]);

            throw $e;
        }
    }
}
