<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'address',
        'timezone',
        'locale',
        'status',
        'owner_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Get the user that owns this organization.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users that belong to this organization.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot(['role_id', 'status', 'joined_at'])
            ->withTimestamps()
            ->using(OrganizationUser::class);
    }

    /**
     * Get the organization users pivot records.
     */
    public function organizationUsers(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Get active organization users.
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('status', 1);
    }

    /**
     * Check if the organization is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Scope to get only active organizations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Check if a user is a member of this organization.
     */
    public function hasMember(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Get the role of a user in this organization.
     */
    public function getUserRole(User $user): ?Role
    {
        $organizationUser = $this->organizationUsers()
            ->where('user_id', $user->id)
            ->first();

        return $organizationUser ? $organizationUser->role : null;
    }

    /**
     * Get the organization subscriptions.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    /**
     * Get the current active subscription.
     */
    public function currentSubscription(): ?OrganizationSubscription
    {
        return $this->subscriptions()->active()->first();
    }

    /**
     * Check if the organization has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->currentSubscription() !== null;
    }

    /**
     * Get subscription limits.
     */
    public function getSubscriptionLimits(): array
    {
        $subscription = $this->currentSubscription()?->subscription;

        if (!$subscription) {
            return [
                'max_chatbots' => 0,
                'max_messages_per_month' => 0,
                'features' => [],
            ];
        }

        return [
            'max_chatbots' => $subscription->max_chatbots,
            'max_messages_per_month' => $subscription->max_messages_per_month,
            'features' => $subscription->features ?? [],
        ];
    }

    /**
     * Get the chatbots associated with the organization.
     */
    public function chatbots(): HasMany
    {
        return $this->hasMany(Chatbot::class);
    }

    /**
     * Get only active chatbots for the organization.
     */
    public function activeChatbots(): HasMany
    {
        return $this->chatbots()->active();
    }

    /**
     * Check if the organization has any active chatbots.
     */
    public function hasActiveChatbots(): bool
    {
        return $this->activeChatbots()->exists();
    }

    /**
     * Get the count of active chatbots for the organization.
     */
    public function getActiveChatbotsCount(): int
    {
        return $this->activeChatbots()->count();
    }

}
