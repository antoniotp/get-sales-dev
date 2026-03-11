<?php

namespace App\Listeners\MessageTemplate;

use App\Contracts\Services\WhatsApp\WhatsAppServiceInterface;
use App\Events\MessageTemplate\MessageTemplateDeleted;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SyncExternalMessageTemplateDeletion implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct( private readonly WhatsAppServiceInterface $whatsAppService)
    {
    }

    public function handle( MessageTemplateDeleted $event ): void
    {
        $template = $event->template;
        $channelId = $template->chatbotChannel->channel_id;
        $channelSlug = $template->chatbotChannel->channel->slug;
        Log::info("Syncing template deletion for channel: {$channelSlug}", ['template_id' => $template->id]);
        try {
            switch ($channelId) {
                case 1: //WABA
                    $this->whatsAppService->deleteTemplate($template);
                    break;
                case 11: //WA-Web
                    break;
            }
        } catch ( Exception $e ) {
            Log::error("Failed to sync template deletion for channel: {$channelSlug}" . $e->getMessage());
            throw $e;
        }
    }
}
