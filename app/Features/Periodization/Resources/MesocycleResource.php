<?php

namespace App\Features\Periodization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MesocycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'macrocycle_id' => $this->macrocycle_id,
            'mesocycle_type' => $this->when(
                $this->relationLoaded('mesocycleType'),
                fn () => [
                    'id' => $this->mesocycleType->id,
                    'name' => $this->mesocycleType->name,
                    'rep_range_min' => $this->mesocycleType->rep_range_min,
                    'rep_range_max' => $this->mesocycleType->rep_range_max,
                ]
            ),
            'split_type' => $this->when(
                $this->relationLoaded('splitType'),
                fn () => [
                    'id' => $this->splitType->id,
                    'name' => $this->splitType->name,
                    'days_per_week' => $this->splitType->days_per_week,
                ]
            ),
            'order' => $this->order,
            'start_week' => $this->start_week,
            'duration_weeks' => $this->duration_weeks,
            'deload_weeks' => $this->deload_weeks,
            'notes' => $this->notes,
            'microcycles_count' => $this->whenCounted('microcycles'),
            'microcycles' => MicrocycleResource::collection($this->whenLoaded('microcycles')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
