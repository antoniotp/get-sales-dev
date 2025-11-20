<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $contact_id
 * @property string $attribute_name
 * @property string|null $attribute_value
 * @property string $source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read ContactEntity $contactEntity
 */
class ContactAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_entity_id',
        'attribute_name',
        'attribute_value',
        'source',
    ];

    /**
     * Get the contact entity that owns the attribute.
     */
    public function contactEntity(): BelongsTo
    {
        return $this->belongsTo(ContactEntity::class);
    }
}
