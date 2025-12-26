<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $subscription_id
 * @property int $status
 * @property Carbon $started_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrganizationSubscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'subscription_id',
        'status',
        'started_at',
        'expires_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this subscription.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the subscription details.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1 &&
            ! $this->isCancelled() &&
            ! $this->isExpired();
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired(): bool
    {
        if (is_null($this->expires_at)) {
            return false; // Lifetime subscription
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_at);
    }

    /**
     * Check if the subscription is a lifetime subscription.
     */
    public function isLifetime(): bool
    {
        return is_null($this->expires_at);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): ?int
    {
        if ($this->isLifetime()) {
            return null;
        }

        return $this->expires_at ? now()->diffInDays($this->expires_at, false) : null;
    }

    /**
     * Check if subscription is expiring soon (within 7 days).
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if ($this->isLifetime() || $this->isExpired()) {
            return false;
        }

        $daysUntilExpiration = $this->getDaysUntilExpiration();

        return $daysUntilExpiration !== null && $daysUntilExpiration <= $days && $daysUntilExpiration > 0;
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(): void
    {
        $this->update([
            'cancelled_at' => now(),
            'status' => 0,
        ]);
    }

    /**
     * Renew the subscription.
     */
    public function renew(?Carbon $newExpirationDate = null): void
    {
        $billingPeriod = $this->subscription->billing_period;

        if (! $newExpirationDate) {
            $currentExpiration = $this->expires_at ?? now();
            $newExpirationDate = $this->calculateNextExpirationDate($currentExpiration, $billingPeriod);
        }

        $this->update([
            'expires_at' => $newExpirationDate,
            'cancelled_at' => null,
            'status' => 1,
        ]);
    }

    /**
     * Calculate next expiration date based on billing period.
     */
    private function calculateNextExpirationDate(Carbon $currentDate, string $billingPeriod): Carbon
    {
        return match ($billingPeriod) {
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'semi-annually' => $currentDate->addMonths(6),
            'annually' => $currentDate->addYear(),
            default => $currentDate->addMonth(),
        };
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1)
            ->whereNull('cancelled_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    /**
     * Scope to get cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    /**
     * Scope to get subscriptions expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->whereNull('cancelled_at');
    }
}
