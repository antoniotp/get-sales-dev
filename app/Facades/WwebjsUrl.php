<?php

namespace App\Facades;

use App\Models\Chatbot;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getUrlForChatbot( Chatbot|string $chatbotIdentifier)
 *
 * @see \App\Services\WhatsApp\WwebjsUrlManager
 */
class WwebjsUrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wwebjs-url-manager';
    }
}
