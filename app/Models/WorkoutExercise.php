<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'order',
        'target_sets',
        'target_reps_min',
        'target_reps_max',
        'target_rpe',
        'rest_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'target_sets' => 'integer',
            'target_reps_min' => 'integer',
            'target_reps_max' => 'integer',
            'target_rpe' => 'integer',
            'rest_seconds' => 'integer',
        ];
    }

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function workoutSets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('set_number');
    }
}
