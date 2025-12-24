<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorePushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_store_a_push_subscription(): void
    {
        $user = User::factory()->create();

        $subscriptionData = [
            'endpoint' => 'https://example.com/some-endpoint/123',
            'keys' => [
                'p256dh' => 'BIPUL12DLfytvTajnryr3vCiKRURJUA_q9_e_22h3dMjiG-A-aM9r3z3g3M',
                'auth' => '5W_Jz-gE1gq9pGgG',
            ],
            'content_encoding' => 'aesgcm',
        ];

        $response = $this->actingAs($user)->postJson(route('notifications.subscriptions.store'), $subscriptionData);

        $response->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $user->id,
            'subscribable_type' => get_class($user),
            'endpoint' => $subscriptionData['endpoint'],
            'public_key' => $subscriptionData['keys']['p256dh'],
            'auth_token' => $subscriptionData['keys']['auth'],
            'content_encoding' => $subscriptionData['content_encoding'],
        ]);
    }
}
