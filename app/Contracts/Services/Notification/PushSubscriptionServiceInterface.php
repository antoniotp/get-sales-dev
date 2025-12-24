<?php

namespace App\Contracts\Services\Notification;

use App\Models\User;
use App\Models\UserPushSubscription;
use Illuminate\Database\Eloquent\Collection;

interface PushSubscriptionServiceInterface
{
    /**
     * Store a new push subscription for a user.
     *
     * @param  User  $user  The user to associate the subscription with.
     * @param  array  $data  The subscription data (endpoint, keys).
     */
    public function store(User $user, array $data): UserPushSubscription;

    /**
     * Remove a push subscription.
     *
     * @param  User  $user  The user who owns the subscription.
     * @param  string  $endpoint  The endpoint of the subscription to remove.
     */
    public function remove(User $user, string $endpoint): void;

    /**
     * Get all active push subscriptions for a user.
     *
     * @return Collection<UserPushSubscription>
     */
    public function getActiveSubscriptions(User $user): Collection;
}
