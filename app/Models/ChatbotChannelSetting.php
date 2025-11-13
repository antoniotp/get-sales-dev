<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotChannelSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'chatbot_channel_id',
        'key',
        'value',
    ];

    /**
     * Get the chatbot channel that owns the setting.
     */
    public function chatbotChannel(): BelongsTo
    {
        return $this->belongsTo(ChatbotChannel::class);
    }
}
