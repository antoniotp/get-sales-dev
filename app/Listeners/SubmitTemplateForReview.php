<?php

namespace App\Listeners;

use App\Contracts\Services\WhatsAppServiceInterface;
use App\Events\MessageTemplateCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SubmitTemplateForReview implements ShouldQueue
{
    /**
     * The WhatsApp service instance.
     *
     * @var WhatsAppServiceInterface
     */
    protected $whatsAppService;

    /**
     * Create the event listener.
     *
     * @param WhatsAppServiceInterface $whatsAppService
     * @return void
     */
    public function __construct(WhatsAppServiceInterface $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Handle the event.
     *
     * @param MessageTemplateCreated $event
     * @return void
     */
    public function handle(MessageTemplateCreated $event)
    {
        try {
            Log::info('Submitting template for review: ' . $event->template->name);

            // Submit the template for review
            $response = $this->whatsAppService->submitTemplateForReview($event->template);

            Log::info('Template submitted successfully', ['response' => $response]);
        } catch (\Exception $e) {
            Log::error('Failed to submit template for review: ' . $e->getMessage(), [
                'template_id' => $event->template->id,
                'exception' => $e
            ]);
        }
    }
}
