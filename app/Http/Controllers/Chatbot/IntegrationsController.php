<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function __construct() {}

    public function index(Chatbot $chatbot): Response
    {
        $linkedChannels = $chatbot->chatbotChannels()->where('credentials', '!=', '')->active()->get()->map(fn ($channel) => [
            'id' => $channel->id,
            'chatbot_id' => $channel->chatbot_id,
            'channel_id' => $channel->channel_id,
            'slug' => $channel->channel->slug,
            'data' => [
                'phone_number_verified_name' => $channel->credentials['phone_number_verified_name'] ?? '',
                'display_phone_number' => $channel->credentials['display_phone_number'] ?? '',
            ],
            'status' => $channel->status,
        ]);

        return Inertia::render('chatbots/integrations', [
            'chatbot' => $chatbot,
            'linkedChannels' => $linkedChannels,
        ]);
    }
}
