<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Argument extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'default_value',
        'schema_id',
        'row',
        'order',
    ];

    // **************************** RELATIONS **************************** //

    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(Option::class);
    }
}
