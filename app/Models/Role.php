<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $can_manage_chats
 * @property int $level
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'can_manage_chats',
        'level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_manage_chats' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Get the organization users for this role.
     */
    public function organizationUsers(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    /**
     * Check if this role can manage chats.
     */
    public function canManageChats(): bool
    {
        return $this->can_manage_chats;
    }

    /**
     * Get roles with a higher level than the current role.
     */
    public function scopeHigherLevel($query, int $level = null)
    {
        $compareLevel = $level ?? $this->level;
        return $query->where('level', '>', $compareLevel);
    }

    /**
     * Get roles with a lower level than the current role.
     */
    public function scopeLowerLevel($query, int $level = null)
    {
        $compareLevel = $level ?? $this->level;
        return $query->where('level', '<', $compareLevel);
    }

    /**
     * Get roles that can manage chats.
     */
    public function scopeCanManageChats($query)
    {
        return $query->where('can_manage_chats', true);
    }

    /**
     * Check if this role has a higher level than another role.
     */
    public function hasHigherLevelThan(Role $role): bool
    {
        return $this->level > $role->level;
    }

    /**
     * Check if this role has a lower level than another role.
     */
    public function hasLowerLevelThan(Role $role): bool
    {
        return $this->level < $role->level;
    }
}
