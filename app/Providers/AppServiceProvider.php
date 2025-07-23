<?php

namespace App\Providers;

use App\Contracts\Services\AIServiceInterface;
use App\Contracts\Services\WhatsAppServiceInterface;
use App\Services\AI\ChatGPTService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(WhatsAppServiceInterface::class, WhatsAppService::class);
        $this->app->bind(AIServiceInterface::class, function ($app) {
            return new ChatGPTService(
                config('services.openai.api_key')
            );
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
