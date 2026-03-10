<?php

namespace App\Enums\MessageTemplate;

enum Status: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAUSED = 'paused';
    case DISABLED = 'disabled';
}
