<?php

namespace App\Factories\WhatsApp;

use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Services\WhatsApp\LegacyWhatsAppWebService;
use App\Services\WhatsApp\WhatsAppWebService;
use App\Services\WhatsApp\WhatsappWebServiceDetector;
use Exception;

class WhatsAppWebServiceFactory
{
    public function __construct(
        private WhatsappWebServiceDetector $detector
    ) {}

    public function create(): WhatsAppWebServiceInterface
    {
        $apiVersion = $this->detector->detect();

        if ($apiVersion === 'legacy') {
            return app(LegacyWhatsAppWebService::class);
        }

        if ($apiVersion === 'new') {
            return app(WhatsAppWebService::class);
        }

        // This should not be reached if the detector works as expected
        throw new Exception('Unknown WhatsApp Web Service API version: '.$apiVersion);
    }
}
