<?php

namespace App\Features\Workout\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'microcycle_id' => $this->microcycle_id,
            'workout_template_id' => $this->workout_template_id,
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'completed_at' => $this->when(
                $this->completed_at !== null,
                fn () => $this->completed_at->toIso8601String()
            ),
            'duration_minutes' => $this->duration_minutes,
            'overall_rpe' => $this->overall_rpe,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
