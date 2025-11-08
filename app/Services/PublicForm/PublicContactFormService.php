<?php

namespace App\Services\PublicForm;

use App\Contracts\Services\PublicForm\PublicContactFormServiceInterface;
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
                        'country_code' => $validatedData['country_code'],
                        'language_code' => $validatedData['language_code'] ?? null,
                        'timezone' => $validatedData['timezone'] ?? null,
                    ]
                );

                // 2. Create the ContactChannel link
                if ($formLink->channel_id) {
                    $contact->contactChannels()->updateOrCreate(
                        [
                            'chatbot_id' => $formLink->chatbot_id,
                            'channel_id' => $formLink->channel_id,
                            'channel_identifier' => $validatedData['phone_number'],
                        ]
                    );
                }

                // 3. Create/update custom attributes
                if (! empty($validatedData['custom_fields'])) {
                    foreach ($validatedData['custom_fields'] as $name => $value) {
                        // Find the field schema to check its type
                        $fieldSchema = collect($formLink->publicFormTemplate->custom_fields_schema)
                            ->firstWhere('name', $name);

                        // Handle checkbox boolean value
                        if ($fieldSchema && $fieldSchema['type'] === 'checkbox') {
                            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        }

                        $contact->contactAttributes()->updateOrCreate(
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
