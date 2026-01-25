<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            // Agregamos un campo calculado como ejemplo (tendencia 2026: datos enriquecidos)
            'initials' => collect(explode(' ', $this->name))->map(fn($n) => mb_substr($n, 0, 1))->join(''),
            'registered_at' => $this->created_at->toIso8601String(),
        ];
    }
}
