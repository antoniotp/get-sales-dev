<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
    public function index(Chatbot $chatbot): Response
    {
        return Inertia::render('chatbots/integrations', [
            'chatbot' => $chatbot,
        ]);
    }
}
