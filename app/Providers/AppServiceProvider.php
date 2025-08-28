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
use App\Contracts\Services\WhatsApp\FacebookServiceInterface;
use App\Services\WhatsApp\FacebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
                config('services.openai.api_key'),
                config('services.openai.model')
            );
        });

        $this->app->bind(RegistrationServiceInterface::class, RegistrationService::class);
        $this->app->bind(OrganizationServiceInterface::class, OrganizationService::class);
        $this->app->bind(FacebookServiceInterface::class, FacebookService::class);

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //explicit binding for chatbot to verify that belongs to the current organization
        Route::bind('chatbot', function ($value) {
            /** @var OrganizationService $organizationService */
            $organizationService = app(OrganizationService::class);
            $request = app(Request::class);
            $user = Auth::user();

            if (!$user) {
                return abort(404, 'User not authenticated.');
            }

            $organization = $organizationService->getCurrentOrganization($request, $user);

            if (!$organization) {
                return abort(404, 'Organization could not be determined.');
            }

            $chatbot = $organization->chatbots()->where('id', $value)->first();

            if (!$chatbot) {
                return abort(403, 'You are not authorized to access this chatbot.');
            }

            return $chatbot;
        });
    }
}
