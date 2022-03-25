<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class UserExperiment extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

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

    public function getResultAttribute(): ?String
    {
        return isset($this->getMedia('result')[0])
            ? $this->getMedia('result')[0]->getFullUrl()
            : null;
    }

    // **************************** SCOPES **************************** //

    public function scopeUnfinished(Builder $query, ?bool $forAuthUser = true): Builder
    {
        if($forAuthUser) $query->where('user_id', auth()->user()->id);

        return $query->where([
            ['filled', null],
            ['deleted_at', null]
        ]);
    }

    public function scopeFilterDevice(Builder $query, int $deviceId): Builder
    {
        return $query->whereHas('experiment', function($q) use ($deviceId) {
            $q->where('device_id', $deviceId);
        });
    }

    // **************************** MEDIA **************************** //

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('result')
//            ->acceptsMimeTypes([
//            ])
            ->singleFile();
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
