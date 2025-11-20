<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $phone_number
 * @property bool $verified_email
 * @property bool $verified_phone
 * @property string|null $country_code
 * @property string|null $language_code
 * @property string|null $timezone
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, ContactEntity> $contactEntities
 * @property-read Collection<int, ContactAttribute> $attributes
 */
class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'verified_email',
        'verified_phone',
        'country_code',
        'language_code',
        'timezone',
    ];

    protected $casts = [
        'verified_email' => 'boolean',
        'verified_phone' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contactChannels(): HasMany
    {
        return $this->hasMany(ContactChannel::class);
    }

    // This relationship contactAttributes() is now in ContactEntity,
    // but commented out to avoid conflicts until confirmed if it should be removed.
    // public function contactAttributes(): HasMany
    // {
    //     return $this->hasMany(ContactAttribute::class);
    // }

    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(Conversation::class, ContactChannel::class);
    }

    /**
     * Get the appointments for the contact.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the contact entities for the contact.
     */
    public function contactEntities(): HasMany
    {
        return $this->hasMany(ContactEntity::class);
    }

    /**
     * Get all attributes for the contact through its entities.
     */
    public function attributes(): HasManyThrough
    {
        return $this->hasManyThrough(ContactAttribute::class, ContactEntity::class);
    }
}
