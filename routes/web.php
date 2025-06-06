<?php

use App\Http\Controllers\Chat\ChatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
    Route::get('/chats', [ChatController::class, 'index'])->name('chats');
    Route::get('/chats/{chatId}/messages', [ChatController::class, 'getMessages'])->name('chats.messages');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
