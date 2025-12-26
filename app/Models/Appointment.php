<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contact_id',
        'chatbot_channel_id',
        'appointment_at',
        'end_at',
        'status',
        'reminder_sent_at',
        'remind_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'appointment_at' => 'datetime',
        'end_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'remind_at' => 'datetime',
    ];

    /**
     * Get the contact that owns the appointment.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the chatbot channel that the appointment is associated with.
     */
    public function chatbotChannel(): BelongsTo
    {
        return $this->belongsTo(ChatbotChannel::class);
    }
}
