<?php

namespace App\Http\Resources\Notification;

use App\DataTransferObjects\Notification\NotificationData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return (array) NotificationData::fromNotification($this->resource);
    }
}
