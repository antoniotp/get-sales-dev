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
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_platform_standard
 * @property int $sort_order
 * @property int $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MessageTemplateCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'is_platform_standard',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'is_platform_standard' => 'boolean',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * Get the templates for this category.
     */
    public function messageTemplates(): HasMany
    {
        return $this->hasMany(MessageTemplate::class, 'category_id');
    }

    /**
     * Scope for active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for standard platform categories
     */
    public function scopePlatformStandard($query)
    {
        return $query->where('is_platform_standard', true);
    }

    /**
     * Scope to sort by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all categories for a select
     */
    public static function getForSelect(): array
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Check if is a standard platform category
     */
    public function isPlatformStandard(): bool
    {
        return $this->is_platform_standard;
    }
}
