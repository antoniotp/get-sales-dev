<?php

namespace App\Services\WhatsApp;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WhatsappWebServiceDetector
{
    private string $wwebjs_url;

    private int $cache_ttl;

    public function __construct(?string $baseUrl = null, ?int $cacheTtl = 300)
    {
        $this->wwebjs_url = $baseUrl ?? rtrim(config('services.wwebjs_service.url'), '/');
        $this->cache_ttl = $cacheTtl;
    }

    public function detect(): string
    {
        return Cache::remember('whatsapp_web_service_detection', $this->cache_ttl, function () {
            try {
                $response = Http::timeout(3)->get($this->wwebjs_url.'/ping');
                if ($response->successful()) {
                    return 'new';
                }
            } catch (Exception $e) {
            }
            try {
                $response = Http::timeout(3)->get($this->wwebjs_url.'/health');
                if ($response->successful()) {
                    return 'legacy';
                }
            } catch (Exception $e) {
            }
            throw new Exception('Could not determine WhatsApp Web Service API version. The service may be down.');
        });
    }

    public function clearCache(): void
    {
        Cache::forget('whatsapp_web_service_detection');
    }
}
