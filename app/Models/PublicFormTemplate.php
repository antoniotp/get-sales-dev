<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PublicFormTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'title',
        'description',
        'custom_fields_schema',
        'entity_config',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_fields_schema' => 'array',
        'entity_config' => 'array',
    ];

    /**
     * Get the public form links for the form template.
     */
    public function publicFormLinks(): HasMany
    {
        return $this->hasMany(PublicFormLink::class);
    }
}
