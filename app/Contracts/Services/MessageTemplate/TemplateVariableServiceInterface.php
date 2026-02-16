<?php

namespace App\Contracts\Services\MessageTemplate;

use App\Models\Chatbot;

interface TemplateVariableServiceInterface
{
    /**
     * Get a structured list of available variables for message templates for a given chatbot.
     *
     * @param  Chatbot  $chatbot  The chatbot for which to retrieve variables.
     * @return array An array of associative arrays, each representing a variable.
     *               Example: [
     *               [
     *               "label" => "Contact: First Name",
     *               "source_path" => "contact.first_name",
     *               "placeholder_name" => "contact_first_name"
     *               ],
     *               // ... other variables
     *               ]
     */
    public function getAvailableVariables(Chatbot $chatbot): array;
}
