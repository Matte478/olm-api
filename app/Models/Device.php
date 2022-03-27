<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'server_id',
        'device_type_id',
        'remote_id'
    ];

    public static function boot()
    {
        parent::boot();

        self::deleting(function (Device $device) {
            foreach ($device->reservations as $reservation) {
                $reservation->delete();
            }
        });
    }

    public function getProductionAttribute()
    {
        return $this->server->production;
    }

    // **************************** SCOPES **************************** //

    public function scopeFilterSoftware(Builder $query, int $softwareId): Builder
    {
        return $query->whereHas('software', function($q) use ($softwareId) {
            $q->where('software_id', $softwareId);
        });
    }

    public function scopeFilterAvailable(Builder $query): Builder
    {
        return $query->whereHas('server', function($q) {
            $q->where([
                'production' => true,
                'enabled' => true,
            ]);
        });
    }

    // **************************** RELATIONS **************************** //

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function software(): BelongsToMany
    {
        return $this->belongsToMany(Software::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function experiment(): HasMany
    {
        return $this->hasMany(Experiment::class);
    }

    public function userExperiments(): HasMany
    {
        return $this->hasMany(UserExperiment::class);
    }
}
