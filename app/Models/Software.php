<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Software extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    // **************************** RELATIONS **************************** //

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class);
    }
}
