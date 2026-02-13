<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutTodayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'image_url' => null,
            'type' => $this->workoutTemplate?->splitType?->name ?? 'Entrenamiento',
            'type_icon_url' => null,
            'duration_minutes' => $this->formatDuration(),
            'exercises_count' => $this->formatExercisesCount(),
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'exercises' => $this->workoutExercises->map(fn ($exercise) => [
                'id' => $exercise->exercise->id,
                'name' => $exercise->exercise->name,
                'order' => $exercise->order,
                'target_sets' => $exercise->target_sets,
                'target_reps_range' => $this->formatRepsRange($exercise->target_reps_min, $exercise->target_reps_max),
                'target_rpe' => $exercise->target_rpe,
                'rest_seconds' => $exercise->rest_seconds,
                'video_url' => $exercise->exercise->video_url,
                'exercise_type' => $exercise->exercise->exerciseType?->name,
                'primary_muscles' => $exercise->exercise->primaryMuscleGroups->pluck('name')->toArray(),
            ]),
        ];
    }

    private function getTitle(): string
    {
        return $this->workoutTemplate?->name ?? 'Entrenamiento del día';
    }

    private function getSubtitle(): string
    {
        $splitType = $this->workoutTemplate?->splitType?->name;

        if ($splitType) {
            return 'Enfócate en la técnica. ¡Vamos con todo!';
        }

        return 'Dale con todo hoy';
    }

    private function formatDuration(): string
    {
        $minutes = $this->duration_minutes ?? $this->workoutTemplate?->estimated_duration_minutes ?? 45;

        return "{$minutes} minutos";
    }

    private function formatExercisesCount(): string
    {
        $count = $this->workoutExercises->count();

        return $count === 1 ? '1 ejercicio' : "{$count} ejercicios";
    }

    private function formatRepsRange(?int $min, ?int $max): string
    {
        if ($min === null && $max === null) {
            return '';
        }

        if ($min === $max || $max === null) {
            return (string) $min;
        }

        if ($min === null) {
            return (string) $max;
        }

        return "{$min}-{$max}";
    }
}
