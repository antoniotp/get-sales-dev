<?php

namespace App\Providers;

use App\Contracts\Services\AI\AIServiceInterface;
use App\Contracts\Services\Appointment\AppointmentServiceInterface;
use App\Contracts\Services\Auth\RegistrationServiceInterface;
use App\Contracts\Services\Chat\ConversationAuthorizationServiceInterface;
use App\Contracts\Services\Chat\ConversationServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Chatbot\ChatbotServiceInterface;
use App\Contracts\Services\Invitation\InvitationServiceInterface;
use App\Contracts\Services\Organization\OrganizationServiceInterface;
use App\Contracts\Services\PublicForm\PublicContactFormServiceInterface;
use App\Contracts\Services\Util\PhoneNumberNormalizerInterface;
use App\Contracts\Services\Util\PhoneServiceInterface;
use App\Contracts\Services\WhatsApp\FacebookServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsAppWebServiceInterface;
use App\Contracts\Services\WhatsApp\WhatsappWebWebhookServiceInterface;
use App\Services\AI\ChatGPTService;
use App\Services\Appointment\AppointmentService;
use App\Services\Auth\RegistrationService;
use App\Services\Chat\ConversationAuthorizationService;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Chatbot\ChatbotService;
use App\Services\Invitation\InvitationService;
use App\Services\Organization\OrganizationService;
use App\Services\PublicForm\PublicContactFormService;
use App\Services\Util\PhoneNumberNormalizer;
use App\Services\Util\PhoneService;
use App\Services\WhatsApp\FacebookService;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppWebService;
use App\Services\WhatsApp\WhatsappWebWebhookService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->app->bind(InvitationServiceInterface::class, InvitationService::class);
        $this->app->bind(ChatbotServiceInterface::class, ChatbotService::class);
        $this->app->bind(ConversationServiceInterface::class, ConversationService::class);
        $this->app->bind(MessageServiceInterface::class, MessageService::class);
        $this->app->bind(AppointmentServiceInterface::class, AppointmentService::class);
        $this->app->bind(ConversationAuthorizationServiceInterface::class, ConversationAuthorizationService::class);

        $this->app->bind(WhatsappWebWebhookServiceInterface::class, WhatsappWebWebhookService::class);
        $this->app->bind(WhatsAppWebServiceInterface::class, WhatsAppWebService::class);
        $this->app->bind(PhoneNumberNormalizerInterface::class, PhoneNumberNormalizer::class);
        $this->app->bind(PhoneServiceInterface::class, PhoneService::class);
        $this->app->bind(PublicContactFormServiceInterface::class, PublicContactFormService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // explicit binding for chatbot to verify that belongs to the current organization
        Route::bind('chatbot', function ($value) {
            /** @var OrganizationService $organizationService */
            $organizationService = app(OrganizationService::class);
            $request = app(Request::class);
            $user = Auth::user();

            if (! $user) {
                return abort(404, 'User not authenticated.');
            }

            $organization = $organizationService->getCurrentOrganization($request, $user);

            if (! $organization) {
                return abort(404, 'Organization could not be determined.');
            }

            $chatbot = $organization->chatbots()->where('id', $value)->first();

            if (! $chatbot) {
                return abort(403, 'You are not authorized to access this chatbot.');
            }

            return $chatbot;
        });

        RateLimiter::for('public-form', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
