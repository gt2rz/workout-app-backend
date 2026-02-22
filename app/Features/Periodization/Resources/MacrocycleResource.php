<?php

namespace App\Features\Periodization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MacrocycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'goal' => $this->goal,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'duration_weeks' => $this->duration_weeks,
            'is_active' => $this->is_active,
            'status' => $this->status,
            'notes' => $this->notes,
            'mesocycles_count' => $this->whenCounted('mesocycles'),
            'mesocycles' => MesocycleResource::collection($this->whenLoaded('mesocycles')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
