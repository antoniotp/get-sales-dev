<?php

namespace App\DataTransferObjects\MessageTemplate;

use App\Models\MessageTemplate;
use Illuminate\Contracts\Support\Arrayable;

class MessageTemplateData implements Arrayable
{
    public function __construct(
        public int $id,
        public string $name,
        public string $category,
        public string $status,
        public int $platformStatus,
        public bool $isDeleted,
        public string $language,
    ) {
    }

    public static function fromMessageTemplate(MessageTemplate $template): self
    {
        return new self(
            id: $template->id,
            name: $template->name,
            category: $template->category->name,
            status: $template->status,
            platformStatus: $template->platform_status,
            isDeleted: (bool)$template->trashed(),
            language: $template->language,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'status' => $this->status,
            'platformStatus' => $this->platformStatus,
            'isDeleted' => $this->isDeleted,
            'language' => $this->language,
        ];
    }
}
