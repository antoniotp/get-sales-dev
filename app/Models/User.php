<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'last_organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the organizations that this user owns.
     */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    /**
     * Get the organizations that this user belongs to.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_users')
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
     * Get active organizations for this user.
     */
    public function activeOrganizations(): BelongsToMany
    {
        return $this->organizations()->wherePivot('status', 1);
    }

    /**
     * Check if the user belongs to an organization.
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()->where('organization_id', $organization->id)->exists();
    }

    /**
     * Get a user's role in a specific organization.
     */
    public function getRoleInOrganization(Organization $organization): ?Role
    {
        $organizationUser = $this->organizationUsers()
            ->where('organization_id', $organization->id)
            ->first();

        return $organizationUser ? $organizationUser->role : null;
    }

    /**
     * Check if a user can manage chats in a specific organization.
     */
    public function canManageChatsInOrganization(Organization $organization): bool
    {
        $role = $this->getRoleInOrganization($organization);
        return $role ? $role->can_manage_chats : false;
    }

    /**
     * Check if a user owns a specific organization.
     */
    public function ownsOrganization(Organization $organization): bool
    {
        return $this->id === $organization->owner_id;
    }

    /**
     * Get the user's last selected organization.
     */
    public function lastOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'last_organization_id');
    }

    /**
     * Get the organization to use as current (last selected or first available).
     */
    public function getCurrentOrganization(): Model
    {
        // First, try to get the last selected organization if it exists and the user still has access
        if ($this->last_organization_id && $this->organizations()->where('organizations.id', $this->last_organization_id)->exists()) {
            return $this->lastOrganization;
        }

        // Fallback to the first available organization
        return $this->organizations()->first();
    }

}
