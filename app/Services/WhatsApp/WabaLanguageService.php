<?php

namespace App\Services\WhatsApp;

use App\Contracts\Services\WhatsApp\WabaLanguageServiceInterface;
use Illuminate\Support\Facades\Config;

class WabaLanguageService implements WabaLanguageServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAll(): array
    {
        return Config::get('waba_languages.full_list', []);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabled(): array
    {
        $fullLanguageList = $this->getAll();
        $enabledLanguageCodes = Config::get('waba_languages.enabled_languages', []);

        return collect($enabledLanguageCodes)->map(function ($code) use ($fullLanguageList) {
            $translationKey = $fullLanguageList[$code] ?? $code;

            return [
                'code' => $code,
                'name' => __($translationKey),
            ];
        })->values()->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabledCodes(): array
    {
        return Config::get('waba_languages.enabled_languages', []);
    }
}
