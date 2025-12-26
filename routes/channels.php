<?php

use App\Models\Chatbot;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('whatsapp-web.chatbot-{chatbotId}', function ($user, $chatbotId) {
    $chatbot = Chatbot::find($chatbotId);

    if (!$chatbot) {
        return false;
    }

    // Check if the user belongs to the organization that owns the chatbot
    return $user->belongsToOrganization($chatbot->organization);
});
