<?php

use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Chat\ChatAssignmentController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chatbot\ChatbotController;
use App\Http\Controllers\Chatbot\ChatbotSwitcherController;
use App\Http\Controllers\Chatbot\IntegrationsController;
use App\Http\Controllers\Chatbot\WhatsAppBusinessIntegrationController;
use App\Http\Controllers\Chatbot\WhatsAppWebIntegrationController;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Facebook\FacebookController;
use App\Http\Controllers\MessageTemplates\MessageTemplateController;
use App\Http\Controllers\Organizations\InvitationController;
use App\Http\Controllers\Organizations\OrganizationController;
use App\Http\Controllers\Organizations\OrganizationMemberController;
use App\Http\Controllers\Organizations\OrganizationSwitchController;
use App\Http\Controllers\Public\PublicFormController;
use App\Http\Controllers\Webhooks\TextMeBotWebhookController;
use App\Http\Controllers\Webhooks\WhatsAppController;
use App\Http\Controllers\Webhooks\WhatsAppWebController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');
Route::get('/policies', function () {
    return Inertia::render('policies');
})->name('policies');
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify'])->name('webhook.whatsapp_business.verify');
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'handle'])->name('webhook.whatsapp_business');
Route::post('/webhook/whatsapp_web', [WhatsAppWebController::class, 'handle'])->name('webhook.whatsapp_web');
Route::post('/webhook/textmebot', [TextMeBotWebhookController::class, 'handle'])->name('webhook.textmebot');

// Public invitation acceptance page
Route::get('/invitations/accept', [InvitationController::class, 'show'])->name('invitations.show');

// Routes for public-facing dynamic forms to register contacts.
Route::get('/forms/{formLink:uuid}', [PublicFormController::class, 'show'])->name('public-forms.show');
Route::post('/forms/{formLink:uuid}', [PublicFormController::class, 'store'])->name('public-forms.store')->middleware('throttle:public-form');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return redirect()->route('chatbots.index');
    })->name('dashboard');
    Route::get('/chatbots', [ChatbotController::class, 'index'])->name('chatbots.index');
    Route::get('/chatbots/create', [ChatbotController::class, 'create'])->name('chatbots.create');
    Route::post('/chatbots', [ChatbotController::class, 'store'])->name('chatbots.store');
    Route::get('/chatbots/{chatbot}', [ChatbotController::class, 'show'])->name('chatbots.show');
    Route::get('/chatbots/{chatbot}/edit', [ChatbotController::class, 'edit'])->name('chatbots.edit');
    Route::put('/chatbots/{chatbot}', [ChatbotController::class, 'update'])->name('chatbots.update');
    Route::delete('/chatbots/{chatbot}', [ChatbotController::class, 'destroy'])->name('chatbots.destroy');
    Route::get('/chatbots/{chatbot}/integrations', [IntegrationsController::class, 'index'])->name('chatbots.integrations');
    Route::get('/chatbots/{chatbot}/integrations/whatsapp-business', [WhatsAppBusinessIntegrationController::class, 'index'])->name('chatbots.integrations.whatsapp-business');
    Route::get('/chatbots/{chatbot}/integrations/whatsapp-web', [WhatsAppWebIntegrationController::class, 'index'])->name('chatbots.integrations.whatsapp-web');
    Route::post('/chatbots/{chatbot}/integrations/whatsapp-web/start', [WhatsAppWebIntegrationController::class, 'startWhatsappWebServer'])->name('chatbots.integrations.whatsapp-web.start');
    Route::get('/chatbots/{chatbot}/integrations/whatsapp-web/status', [WhatsAppWebIntegrationController::class, 'getWhatsAppWebStatus'])->name('chatbots.integrations.whatsapp-web.status');
    Route::post('/chatbots/{chatbot}/integrations/whatsapp-web/reconnect', [WhatsAppWebIntegrationController::class, 'reconnectWhatsappWebSession'])->name('chatbots.integrations.whatsapp-web.reconnect');
    Route::get('/chatbots/{chatbot}/chats/{conversation?}', [ChatController::class, 'index'])->name('chats');
    Route::post('/chatbots/{chatbot}/chats', [ChatController::class, 'store'])->name('chats.store');
    Route::get('/chatbots/{chatbot}/chats/start/{phone_number}', [ChatController::class, 'startFromLink'])->name('chats.start'); // required: "?cc_id={chatbot_channel_id}" optional: "?text={initial%20message}"
    Route::get('/chats/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chats.messages');
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chats.messages.store');
    Route::put('/chats/{conversation}/mode', [ChatController::class, 'updateConversationMode'])->name('chats.mode.update');
    Route::put('/chats/{conversation}/assignment', [ChatAssignmentController::class, 'update'])->name('chats.assignment.update');
    Route::get('/chatbots/{chatbot}/message_templates', [MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::get('/message_templates/create', [MessageTemplateController::class, 'create'])->name('message-templates.create');
    Route::post('/message_templates', [MessageTemplateController::class, 'store'])->name('message-templates.store');
    Route::get('/message_templates/{template}/edit', [MessageTemplateController::class, 'edit'])->name('message-templates.edit');
    Route::put('/message_templates/{template}', [MessageTemplateController::class, 'update'])->name('message-templates.update');
    Route::delete('/message_templates/{template}', [MessageTemplateController::class, 'destroy'])->name('message-templates.destroy');

    Route::post('/messages/{message}/retry', [ChatController::class, 'retryMessage'])->name('messages.retry');

    Route::get('/chatbots/{chatbot}/calendar', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/chatbots/{chatbot}/appointments', [AppointmentController::class, 'list'])->name('appointments.list');
    Route::post('/chatbots/{chatbot}/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'upsert'])->name('contacts.store');
    Route::put('/contacts/{contact}', [ContactController::class, 'upsert'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/contacts/search', [ContactController::class, 'search'])->name('contacts.search');
    Route::post('/organizations/switch', [OrganizationSwitchController::class, 'switch'])->name('organizations.switch');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/settings', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    //    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::get('/organizations/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
    Route::post('/invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
    Route::post('/invitations/accept/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/chatbots/{chatbot}/integrations/facebook/callback', [FacebookController::class, 'handleCallback'])->name('facebook.callback');

    Route::get('/chatbot_switcher', [ChatbotSwitcherController::class, 'list'])->name('chatbot_switcher.list');
    Route::post('/chatbot_switcher', [ChatbotSwitcherController::class, 'switch'])->name('chatbot_switcher.switch');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
