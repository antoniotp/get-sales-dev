<?php

namespace App\DataTransferObjects\Chat;

class MessageSendResult
{
    public function __construct(
        public readonly ?string $externalId = null
    ) {
    }
}
