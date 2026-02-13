<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'microcycle_id',
        'workout_template_id',
        'scheduled_date',
        'completed_at',
        'duration_minutes',
        'overall_rpe',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed_at' => 'datetime',
            'duration_minutes' => 'integer',
            'overall_rpe' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function microcycle(): BelongsTo
    {
        return $this->belongsTo(Microcycle::class);
    }

    public function workoutTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkoutTemplate::class);
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('order');
    }

    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    public function scopeScheduledForToday(Builder $query): void
    {
        $query->whereDate('scheduled_date', today());
    }

    public function scopeScheduledFor(Builder $query, $date): void
    {
        $query->whereDate('scheduled_date', $date);
    }

    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', 'completed');
    }

    public function scopeWithFullDetails(Builder $query): void
    {
        $query->with([
            'workoutTemplate:id,name,description,split_type_id,estimated_duration_minutes',
            'workoutTemplate.splitType:id,name',
            'workoutExercises' => function ($q) {
                $q->orderBy('order');
            },
            'workoutExercises.exercise:id,name,video_url,exercise_type_id',
            'workoutExercises.exercise.exerciseType:id,name',
            'workoutExercises.exercise.primaryMuscleGroups:id,name',
        ]);
    }
}
