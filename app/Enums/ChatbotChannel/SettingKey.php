<?php

namespace App\Enums\ChatbotChannel;

/**
 * Define chatbot_channel_settings keys
 */
enum SettingKey: string
{
    /**
     * The text message to send when a WhatsApp call is rejected.
     * Expected value: string.
     */
    case CALL_REJECTION_MESSAGE = 'call_rejection_message';
}
