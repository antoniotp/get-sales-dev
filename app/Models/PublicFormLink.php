<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PublicFormLink extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'public_form_template_id',
        'chatbot_id',
        'channel_id',
        'is_active',
        'success_message',
        'redirect_on_success',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Generate a new UUID for the model before creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setAttribute($model->getKeyName(), Str::uuid());
        });
    }

    /**
     * Get the form template that owns the public form link.
     */
    public function publicFormTemplate(): BelongsTo
    {
        return $this->belongsTo(PublicFormTemplate::class);
    }

    /**
     * Get the chatbot that the public form link is associated with.
     */
    public function chatbot(): BelongsTo
    {
        return $this->belongsTo(Chatbot::class);
    }

    /**
     * Get the channel that the public form link is associated with.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
