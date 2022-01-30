<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserExperiment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'experiment_id',
        'schema_id',
        'input',
        'output',
        'note',
        'simulation_time',
        'sampling_rate',
        'filled',
    ];

    // **************************** RELATIONS **************************** //

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function schema(): BelongsTo
    {
        return $this->belongsTo(Schema::class);
    }
}
