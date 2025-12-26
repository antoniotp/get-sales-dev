<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $country_code
 * @property float $price
 * @property string $currency
 * @property string $billing_period
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class SubscriptionPricing extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_id',
        'country_code',
        'price',
        'currency',
        'billing_period',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the subscription that owns this pricing.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get a formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get the monthly equivalent price.
     */
    public function getMonthlyEquivalentPrice(): float
    {
        return match ($this->billing_period) {
            'monthly' => $this->price,
            'quarterly' => $this->price / 3,
            'semi-annually' => $this->price / 6,
            'annually' => $this->price / 12,
            default => $this->price,
        };
    }

    /**
     * Get billing period multiplier.
     */
    public function getBillingPeriodMultiplier(): int
    {
        return match ($this->billing_period) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi-annually' => 6,
            'annually' => 12,
            default => 1,
        };
    }

    /**
     * Scope to filter by country.
     */
    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to filter by billing period.
     */
    public function scopeForBillingPeriod($query, string $period)
    {
        return $query->where('billing_period', $period);
    }

    /**
     * Scope to filter by currency.
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Check if this is a monthly billing.
     */
    public function isMonthly(): bool
    {
        return $this->billing_period === 'monthly';
    }

    /**
     * Check if this is an annual billing.
     */
    public function isAnnual(): bool
    {
        return $this->billing_period === 'annually';
    }
}
