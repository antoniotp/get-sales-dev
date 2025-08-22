<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property int $role_id
 * @property int $status
 * @property Carbon $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class OrganizationUser extends Pivot
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organization_users';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'role_id',
        'status',
        'joined_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'joined_at' => 'datetime',
    ];

    /**
     * Get the organization that owns this pivot record.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that owns this pivot record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role for this organization user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if the organization user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 1;
    }

    /**
     * Scope to get only active organization users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get organization users with chat management permissions.
     */
    public function scopeCanManageChats($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('can_manage_chats', true);
        });
    }

    /**
     * Check if this organization user can manage chats.
     */
    public function canManageChats(): bool
    {
        return $this->role?->can_manage_chats ?? false;
    }

    /**
     * Get the role level for this organization user.
     */
    public function getRoleLevel(): int
    {
        return $this->role?->level ?? 0;
    }
}
