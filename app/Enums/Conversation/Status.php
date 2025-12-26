<?php

namespace App\Enums\Conversation;

enum Status: int
{
    case ACTIVE = 1;
    case PENDING_NOTIFICATION = 2;
}
