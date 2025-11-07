<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicFormTemplate extends Model
{
    use HasFactory;

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_fields_schema' => 'array',
    ];

    /**
     * Get the public form links for the form template.
     */
    public function publicFormLinks(): HasMany
    {
        return $this->hasMany(PublicFormLink::class);
    }
}
