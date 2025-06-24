<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplateSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_template_id',
        'conversation_id',
        'message_id',
        'variables_data',
        'rendered_content',
        'platform_message_id',
        'send_status',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'variables_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the related template
     */
    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    /**
     * Get the related conversation
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the related message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Scope to filter successfully sent messages.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('send_status', ['sent', 'delivered', 'read']);
    }

    /**
     * Scope to filter failed sent messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('send_status', 'failed');
    }

    /**
     * Scope to filter pending messages.
     */
    public function scopePending($query)
    {
        return $query->where('send_status', 'pending');
    }

    /**
     * Check if the message was sent successfully
     */
    public function isSuccessful(): bool
    {
        return in_array($this->send_status, ['sent', 'delivered', 'read']);
    }

    public function isFailed(): bool
    {
        return $this->send_status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->send_status === 'pending';
    }

    public function markAsSent(string $platformMessageId = null): void
    {
        $this->update([
            'send_status' => 'sent',
            'platform_message_id' => $platformMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'send_status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        $this->update([
            'send_status' => 'read',
            'read_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorCode = null, string $errorMessage = null): void
    {
        $this->update([
            'send_status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }
}
