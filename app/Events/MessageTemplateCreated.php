<?php

namespace App\Events;

use App\Models\MessageTemplate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageTemplateCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message template instance.
     *
     * @var MessageTemplate
     */
    public $template;

    /**
     * Create a new event instance.
     *
     * @param MessageTemplate $template
     * @return void
     */
    public function __construct(MessageTemplate $template)
    {
        $this->template = $template;
    }
}
