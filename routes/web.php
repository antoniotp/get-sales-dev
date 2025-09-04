<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chatbot\ChatbotController;
use App\Http\Controllers\Chatbot\IntegrationsController;
use App\Http\Controllers\Chatbot\WhatsAppIntegrationController;
use App\Http\Controllers\Contacts\ContactController;
use App\Http\Controllers\Facebook\FacebookController;
use App\Http\Controllers\MessageTemplates\MessageTemplateController;
use App\Http\Controllers\Organizations\OrganizationController;
use App\Http\Controllers\Organizations\OrganizationMemberController;
use App\Http\Controllers\Organizations\InvitationController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\Webhooks\WhatsAppController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () { return Inertia::render('home'); })->name('home');
Route::get('/policies', function () { return Inertia::render('policies'); })->name('policies');
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'handle']);

// Public invitation acceptance page
Route::get('/invitations/accept', [InvitationController::class, 'show'])->name('invitations.show');


Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('dashboard', function () { return redirect()->route('chatbots.index'); })->name('dashboard');
    Route::get('/chatbots', [ChatbotController::class, 'index'])->name('chatbots.index');
    Route::get('/chatbots/create', [ChatbotController::class, 'create'])->name('chatbots.create');
    Route::post('/chatbots', [ChatbotController::class, 'store'])->name('chatbots.store');
    Route::get('/chatbots/{chatbot}', [ChatbotController::class, 'show'])->name('chatbots.show');
    Route::get('/chatbots/{chatbot}/edit', [ChatbotController::class, 'edit'])->name('chatbots.edit');
    Route::put('/chatbots/{chatbot}', [ChatbotController::class, 'update'])->name('chatbots.update');
    Route::delete('/chatbots/{chatbot}', [ChatbotController::class, 'destroy'])->name('chatbots.destroy');
    Route::get('/chatbots/{chatbot}/integrations', [ IntegrationsController::class, 'index'])->name('chatbots.integrations');
    Route::get('/chatbots/{chatbot}/integrations/whatsapp', [ WhatsAppIntegrationController::class, 'index'])->name('chatbots.integrations.whatsapp');
    Route::get('/chatbots/{chatbot}/chats', [ChatController::class, 'index'])->name('chats');
    Route::get('/chats/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chats.messages');
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chats.messages.store');
    Route::put('/chats/{conversation}/mode', [ChatController::class, 'updateConversationMode'])->name('chats.mode.update');
    Route::get('/chatbots/{chatbot}/message_templates', [ MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::get('/message_templates/create', [MessageTemplateController::class, 'create'])->name('message-templates.create');
    Route::post('/message_templates', [MessageTemplateController::class, 'store'])->name('message-templates.store');
    Route::get('/message_templates/{template}/edit', [MessageTemplateController::class, 'edit'])->name('message-templates.edit');
    Route::put('/message_templates/{template}', [MessageTemplateController::class, 'update'])->name('message-templates.update');
    Route::delete('/message_templates/{template}', [MessageTemplateController::class, 'destroy'])->name('message-templates.destroy');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [ContactController::class, 'upsert'])->name('contacts.store');
    Route::put('/contacts/{contact}', [ContactController::class, 'upsert'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('/organizations/switch', [OrganizationSwitchController::class, 'switch'])->name('organizations.switch');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/settings', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
//    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::get('/organizations/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::post('/invitations/accept/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/chatbots/{chatbot}/integrations/facebook/callback', [FacebookController::class, 'handleCallback'])->name('facebook.callback');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
