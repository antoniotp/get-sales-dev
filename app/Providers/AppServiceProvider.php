<?php

namespace App\Providers;

use App\Contracts\Services\WhatsAppServiceInterface;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
