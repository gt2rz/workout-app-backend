<?php

namespace App\Features\Periodization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MicrocycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mesocycle_id' => $this->mesocycle_id,
            'week_number' => $this->week_number,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'is_deload' => $this->is_deload,
            'target_volume_percentage' => $this->target_volume_percentage,
            'actual_volume_completed' => $this->actual_volume_completed,
            'status' => $this->status,
            'sessions_count' => $this->whenCounted('workoutSessions'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
