<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\MessageTemplates\MessageTemplateController;
use App\Http\Controllers\Webhooks\WhatsAppController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'handle']);


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('/chats', [ChatController::class, 'index'])->name('chats');
    Route::get('/chats/{conversation}/messages', [ChatController::class, 'getMessages'])->name('chats.messages');
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])
        ->name('chats.messages.store');
    Route::get('/message_templates', [ MessageTemplateController::class, 'index'])->name('message_templates.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
