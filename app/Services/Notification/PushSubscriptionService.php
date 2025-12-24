<?php

namespace App\Services\Notification;

use App\Contracts\Services\Notification\PushSubscriptionServiceInterface;
use App\Models\User;
use App\Models\UserPushSubscription;
use Illuminate\Database\Eloquent\Collection;

class PushSubscriptionService implements PushSubscriptionServiceInterface
{
    /**
     * Store a new push subscription for a user.
     *
     * @param User $user The user to associate the subscription with.
     * @param array $data The subscription data (endpoint, keys).
     * @return UserPushSubscription
     */
    public function store(User $user, array $data): UserPushSubscription
    {
        $subscription = $user->pushSubscriptions()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'public_key' => $data['keys']['p256dh'] ?? null,
                'auth_token' => $data['keys']['auth'] ?? null,
                'content_encoding' => $data['content_encoding'] ?? null,
            ]
        );

        return $subscription;
    }

    /**
     * Remove a push subscription.
     *
     * @param User $user The user who owns the subscription.
     * @param string $endpoint The endpoint of the subscription to remove.
     * @return void
     */
    public function remove(User $user, string $endpoint): void
    {
        $user->pushSubscriptions()->where('endpoint', $endpoint)->delete();
    }

    /**
     * Get all active push subscriptions for a user.
     *
     * @param User $user
     * @return Collection<UserPushSubscription>
     */
    public function getActiveSubscriptions(User $user): Collection
    {
        return $user->pushSubscriptions;
    }
}
