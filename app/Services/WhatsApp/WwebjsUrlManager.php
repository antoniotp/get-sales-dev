<?php

namespace App\Services\WhatsApp;

use App\Models\Chatbot;
use Illuminate\Support\Str;

class WwebjsUrlManager
{
    /**
     * URLs for specific chatbots.
     * The key is the chatbot ID (int).
     */
    private const SPECIAL_URLS = [
        '19' => 'http://wweb-api.get-sales.com:8080/',
        '21' => 'http://wweb-api.get-sales.com:8080/',
    ];

    public function __construct(private readonly string $defaultUrl) {}

    /**
     * Gets the URL for a specific chatbot.
     *
     * @param  Chatbot|string  $chatbotIdentifier  Could be a Chatbot object, the chatbot ID (int), or the chatbot session ID (ejb. "chatbot-19").
     */
    public function getUrlForChatbot(Chatbot|string $chatbotIdentifier): string
    {
        $chatbotId = $this->extractChatbotId($chatbotIdentifier);

        // Search in the special URLs using the extracted chatbot ID.
        if (array_key_exists($chatbotId, self::SPECIAL_URLS)) {
            return rtrim(self::SPECIAL_URLS[$chatbotId], '/');
        }

        // Return the default URL if no special URL was found.
        return $this->defaultUrl;
    }

    /**
     * Extracts the chatbot ID (int) from different formats.
     */
    private function extractChatbotId(Chatbot|string $identifier): string
    {
        if ($identifier instanceof Chatbot) {
            return (string) $identifier->id;
        }

        // if "chatbot-19" is passed, return "19".
        if (Str::startsWith($identifier, 'chatbot-')) {
            return Str::after($identifier, 'chatbot-');
        }

        return (string) $identifier;
    }
}
