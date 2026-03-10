<?php

namespace App\Enums\MessageTemplate;

enum HeaderType: string
{
    case NONE = 'none';
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
}
