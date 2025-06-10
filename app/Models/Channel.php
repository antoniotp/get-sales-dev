<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'webhook_fields',
        'credentials_fields',
        'status',
    ];

    protected $casts = [
        'webhook_fields' => 'array',
        'credentials_fields' => 'array',
        'status' => 'integer',
    ];

    /**
     * Get the chatbot channels for this channel.
     */
    public function chatbotChannels(): HasMany
    {
        return $this->hasMany(ChatbotChannel::class);
    }

    /**
     * Scope a query to only include active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
