<?php

namespace App\Contracts\Services\WhatsApp;

interface WabaLanguageServiceInterface
{
    /**
     * Get a list of all WABA languages.
     *
     * @return array<string, string> Key is language code, value is a display name.
     */
    public function getAll(): array;

    /**
     * Get a list of enabled WABA languages, formatted for frontend use.
     *
     * @return array<array{code: string, name: string}>
     */
    public function getEnabled(): array;

    /**
     * Get a list of enabled WABA language codes.
     *
     * @return array<string>
     */
    public function getEnabledCodes(): array;
}
