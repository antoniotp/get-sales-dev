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

    public function contactAttributes(): HasMany
    {
        return $this->hasMany(ContactAttribute::class);
    }
}
