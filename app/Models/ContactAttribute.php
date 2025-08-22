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
 */
class ContactAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_id',
        'attribute_name',
        'attribute_value',
        'source',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
