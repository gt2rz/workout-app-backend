<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Microcycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'mesocycle_id',
        'week_number',
        'start_date',
        'end_date',
        'is_deload',
        'target_volume_percentage',
        'actual_volume_completed',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_deload' => 'boolean',
            'week_number' => 'integer',
            'target_volume_percentage' => 'integer',
        ];
    }

    public function mesocycle(): BelongsTo
    {
        return $this->belongsTo(Mesocycle::class);
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    public function scopeDeload(Builder $query): void
    {
        $query->where('is_deload', true);
    }
}
