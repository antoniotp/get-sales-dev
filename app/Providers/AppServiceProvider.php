<?php

namespace App\Providers;

use App\Contracts\Services\AIServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Contracts\Services\WhatsAppServiceInterface;
use App\Services\AI\ChatGPTService;
use App\Services\Organization\OrganizationService;
use App\Services\WhatsApp\WhatsAppService;
use App\Contracts\Services\Auth\RegistrationServiceInterface;
use App\Services\Auth\RegistrationService;
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

        $this->app->bind(RegistrationServiceInterface::class, RegistrationService::class);
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
