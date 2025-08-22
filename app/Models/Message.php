<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property string|null $external_message_id
 * @property string $type
 * @property string $content
 * @property string $content_type
 * @property string|null $media_url
 * @property string $sender_type
 * @property int|null $sender_user_id
 * @property array|null $metadata
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $failed_at
 * @property string|null $error_message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'external_message_id',
        'type',
        'content',
        'content_type',
        'media_url',
        'sender_type',
        'sender_user_id',
        'metadata',
        'delivered_at',
        'read_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'metadata' => 'array',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns this message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message (if sent by human).
     */
    public function senderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Scope a query to only include incoming messages.
     */
    public function scopeIncoming($query)
    {
        return $query->where('type', 'incoming');
    }

    /**
     * Scope a query to only include outgoing messages.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('type', 'outgoing');
    }

    /**
     * Scope a query to only include messages from contacts.
     */
    public function scopeFromContact($query)
    {
        return $query->where('sender_type', 'contact');
    }

    /**
     * Scope a query to only include messages from AI.
     */
    public function scopeFromAi($query)
    {
        return $query->where('sender_type', 'ai');
    }

    /**
     * Scope a query to only include messages from humans.
     */
    public function scopeFromHuman($query)
    {
        return $query->where('sender_type', 'human');
    }

    /**
     * Check if a message is incoming.
     */
    public function isIncoming(): bool
    {
        return $this->type === 'incoming';
    }

    /**
     * Check if a message is outgoing.
     */
    public function isOutgoing(): bool
    {
        return $this->type === 'outgoing';
    }

    /**
     * Check if a message was sent by contact.
     */
    public function isFromContact(): bool
    {
        return $this->sender_type === 'contact';
    }

    /**
     * Check if a message was sent by AI.
     */
    public function isFromAi(): bool
    {
        return $this->sender_type === 'ai';
    }

    /**
     * Check if a message was sent by a human.
     */
    public function isFromHuman(): bool
    {
        return $this->sender_type === 'human';
    }

    /**
     * Check if a message has media content.
     */
    public function hasMedia(): bool
    {
        return !empty($this->media_url);
    }

    /**
     * Check if message delivery failed.
     */
    public function hasFailed(): bool
    {
        return !is_null($this->failed_at);
    }

    /**
     * Check if a message was delivered.
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * Check if a message was read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }
}
