<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $contact_channel_id
 * @property int $chatbot_channel_id
 * @property string $external_conversation_id
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $contact_avatar
 * @property int $status
 * @property string $mode
 * @property int|null $assigned_user_id
 * @property Carbon|null $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Conversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_channel_id',
        'chatbot_channel_id',
        'external_conversation_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'contact_avatar',
        'status',
        'mode',
        'assigned_user_id',
        'last_message_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the chatbot channel that owns this conversation.
     */
    public function chatbotChannel(): BelongsTo
    {
        return $this->belongsTo(ChatbotChannel::class);
    }

    /**
     * Get the contact channel that owns this conversation.
     */
    public function contactChannel(): BelongsTo
    {
        return $this->belongsTo(ContactChannel::class);
    }

    /**
     * Get the assigned user for this conversation.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Get the messages for this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Get the latest message for this conversation.
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest();
    }

    /**
     * Scope a query to only include active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include AI mode conversations.
     */
    public function scopeAiMode($query)
    {
        return $query->where('mode', 'ai');
    }

    /**
     * Scope a query to only include human mode conversations.
     */
    public function scopeHumanMode($query)
    {
        return $query->where('mode', 'human');
    }

    /**
     * Check if conversation is in AI mode.
     */
    public function isAiMode(): bool
    {
        return $this->mode === 'ai';
    }

    /**
     * Check if conversation is in human mode.
     */
    public function isHumanMode(): bool
    {
        return $this->mode === 'human';
    }
}
