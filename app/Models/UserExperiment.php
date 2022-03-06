<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        'remote_id'
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    // **************************** SCOPES **************************** //

    public function scopeUnfilled(Builder $query, ?bool $forAuthUser = true): Builder
    {
        if($forAuthUser) $query->where('user_id', auth()->user()->id);

        return $query->where([
            ['filled', false],
            ['deleted_at', null]
        ]);
    }

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
