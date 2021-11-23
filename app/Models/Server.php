<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'websocket_port',
        'available',
        'production',
        'enabled',
    ];

    // **************************** RELATIONS **************************** //

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
