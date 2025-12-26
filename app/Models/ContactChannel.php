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
 * @property int $contact_id
 * @property int $chatbot_id
 * @property int|null $channel_id
 * @property string $channel_identifier
 * @property array|null $channel_data
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class ContactChannel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_id',
        'chatbot_id',
        'channel_id',
        'channel_identifier',
        'channel_data',
        'is_primary',
    ];

    protected $casts = [
        'channel_data' => 'array',
        'is_primary' => 'boolean',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
