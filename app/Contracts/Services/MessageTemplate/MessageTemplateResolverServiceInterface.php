<?php

namespace App\Contracts\Services\MessageTemplate;

use App\Models\Contact;
use App\Models\MessageTemplate;
use App\Models\User;

interface MessageTemplateResolverServiceInterface
{
    /**
     * Resolves the variable mappings of a template into actual values for a given contact.
     *
     * @param  MessageTemplate  $template  The template containing the mappings.
     * @param  Contact  $contact  The contact to fetch data from.
     * @param  array  $manualValues  Key-value pairs for variables marked as 'manual'.
     * @param  User|null  $user  Optional user (agent) context for user-related variables.
     * @return array Structured array with resolved values for header and body.
     *               Example: [
     *               'header' => ['placeholder' => '{{1}}', 'value' => 'Cris'],
     *               'body' => [
     *               ['placeholder' => '{{company}}', 'value' => 'GetSales'],
     *               ['placeholder' => '{{manual_var}}', 'value' => 'User input']
     *               ]
     *               ]
     */
    public function resolveValues(
        MessageTemplate $template,
        Contact $contact,
        array $manualValues = [],
        ?User $user = null
    ): array;

    /**
     * Renders the template content by replacing placeholders with resolved values.
     *
     * @param  MessageTemplate  $template  The template to render.
     * @param  array  $resolvedValues  The output from resolveValues().
     * @return array Array with 'header', 'body', and 'footer' rendered strings.
     */
    public function render(MessageTemplate $template, array $resolvedValues): array;
}
