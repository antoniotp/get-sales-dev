<?php

namespace App\Events\MessageTemplate;

use App\Models\MessageTemplate;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageTemplateDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MessageTemplate $template) {}
}
