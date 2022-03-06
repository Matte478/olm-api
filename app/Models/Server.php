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
        'domain',
        'port',
        'websocket_port',
        'available',
        'production',
        'enabled',
    ];

    protected $attributes = [
        'available' => false,
        'production' => false,
        'enabled' => false,
    ];

    public static function boot()
    {
        parent::boot();

        self::deleting(function (Server $server) {
            foreach ($server->devices as $device) {
                $device->delete();
            }
        });
    }

    // **************************** RELATIONS **************************** //

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function experiment(): HasMany
    {
        return $this->hasMany(Experiment::class);
    }
}
