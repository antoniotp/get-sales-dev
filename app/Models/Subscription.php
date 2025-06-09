<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'is_free',
        'max_chatbots',
        'max_messages_per_month',
        'features',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_free' => 'boolean',
        'max_chatbots' => 'integer',
        'max_messages_per_month' => 'integer',
        'features' => 'array',
        'status' => 'integer',
    ];

    /**
     * Get the subscription pricing for this subscription.
     */
    public function pricing(): HasMany
    {
        return $this->hasMany(SubscriptionPricing::class);
    }

    /**
     * Get the organization subscriptions for this subscription.
     */
    public function organizationSubscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    /**
     * Get active organization subscriptions.
     */
    public function activeOrganizationSubscriptions(): HasMany
    {
        return $this->organizationSubscriptions()->where('status', 1);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if the subscription is free.
     */
    public function isFree(): bool
    {
        return $this->is_free;
    }

    /**
     * Check if chatbots are unlimited.
     */
    public function hasUnlimitedChatbots(): bool
    {
        return is_null($this->max_chatbots);
    }

    /**
     * Check if messages are unlimited.
     */
    public function hasUnlimitedMessages(): bool
    {
        return is_null($this->max_messages_per_month);
    }

    /**
     * Get a price for a specific country and billing period.
     */
    public function getPriceForCountry(string $countryCode, string $billingPeriod = null): ?SubscriptionPricing
    {
        $query = $this->pricing()->where('country_code', $countryCode);

        if ($billingPeriod) {
            $query->where('billing_period', $billingPeriod);
        } else {
            $query->where('billing_period', $this->billing_period);
        }

        return $query->first();
    }

    /**
     * Check if the subscription has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Scope to get only active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get only free subscriptions.
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope to get only paid subscriptions.
     */
    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    /**
     * Scope to filter by billing period.
     */
    public function scopeByBillingPeriod($query, string $period)
    {
        return $query->where('billing_period', $period);
    }
}
