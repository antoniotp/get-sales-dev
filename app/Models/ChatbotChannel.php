<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chatbot_id',
        'channel_id',
        'name',
        'webhook_url',
        'credentials',
        'webhook_config',
        'status',
        'last_activity_at',
    ];

    protected $casts = [
        'credentials' => 'array',
        'webhook_config' => 'array',
        'status' => 'integer',
        'last_activity_at' => 'datetime',
    ];

    /**
     * Get the chatbot that owns this channel.
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    /**
     * Get the channel associated with this chatbot channel.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the conversations for this chatbot channel.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Scope a query to only include active chatbot channels.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
