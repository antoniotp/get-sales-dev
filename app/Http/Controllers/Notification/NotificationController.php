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

    /**
     * Delete all read notifications for the user.
     */
    public function clearRead(Request $request): JsonResponse
    {
        $request->user()->readNotifications()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all notifications for the user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get the current status of user notifications (e.g., unread count).
     */
    public function status(Request $request): JsonResponse
    {
        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json(['unread_count' => $unreadCount]);
    }
}
