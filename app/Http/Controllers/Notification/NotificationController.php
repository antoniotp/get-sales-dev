<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notification\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Get the user's recent notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $notifications = $user->notifications()->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all of the user's unread notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
