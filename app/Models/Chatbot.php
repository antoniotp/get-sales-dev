<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chatbot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'system_prompt',
        'status',
        'response_delay_min',
        'response_delay_max',
    ];

    protected $casts = [
        'status' => 'integer',
        'response_delay_min' => 'integer',
        'response_delay_max' => 'integer',
    ];

    /**
     * Get the organization that owns the chatbot.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the chatbot channels for this chatbot.
     */
    public function chatbotChannels(): HasMany
    {
        return $this->hasMany(ChatbotChannel::class);
    }

    /**
     * Get the contact channels for this chatbot.
     */
    public function contactChannels(): HasMany
    {
        return $this->hasMany(ContactChannel::class);
    }

    /**
     * Get all conversations through chatbot channels.
     */
    public function conversations(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            ChatbotChannel::class,
            'chatbot_id',
            'chatbot_channel_id'
        );
    }

    /**
     * Scope a query to only include active chatbots.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
