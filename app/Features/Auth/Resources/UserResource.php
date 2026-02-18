<?php

namespace App\Features\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'initials' => collect(explode(' ', $this->name))->map(fn ($n) => mb_substr($n, 0, 1))->join(''),
            'registered_at' => $this->created_at->toIso8601String(),
        ];
    }
}
