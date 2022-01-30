<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Experiment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'server_id',
        'device_type_id',
        'device_id',
        'software_id',
        'commands',
        'experiment_commands',
        'output_arguments',
        'has_schema',
        'deleted_at'
    ];

    // **************************** RELATIONS **************************** //

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function userExperiments(): HasMany
    {
        return $this->hasMany(Experiment::class);
    }
}
