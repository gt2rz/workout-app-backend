<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // --- Bloque original de datos estáticos, conservado para referencia ---
        /*
        $user = Auth::user();
        return [
            'id' => 1,
            'user_id' => $user->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'bio' => 'This is a sample bio for user',
            'avatar' => 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?s=200&d=identicon',
            'created_at' => '2024-01-15',
            'updated_at' => '2024-06-01',
            'preferences' => [
                'notifications' => true,
                'dark_mode' => false,
            ],
            'membership' => [
                'id' => 1,
                'name' => 'Basic',
                'status' => true,
                'expires_at' => '2026-12-31',
            ],
            'stats' => [
                'workouts_completed' => 42,
                'total_hours' => 100,
                'streak' => 7,
                'weight' => [
                    'actual' => '103.5 kg',
                    'monthly_difference' => '+0.5 kg',
                    'monthly_progress' => [
                        '2025-09' => '105 kg',
                        '2025-10' => '104.5 kg',
                        '2025-11' => '104 kg',
                        '2025-12' => '103.8 kg',
                        '2026-01' => '103.5 kg'
                    ]
                ]
            ],
            'menu' => [
                'show_edit_profile' => true,
                'show_change_password' => true,
                'show_membership' => true,
                'show_preferences' => true,
                'show_statistics' => true,
                'show_logout' => true,
                'show_delete_account' => false,
                'show_goals' => true,
                'show_routine_history' => true,
            ]
        ];
        */

        // --- Bloque activo: datos reales del modelo ---
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'bio' => $this->bio ?? '',
            'avatar' => $this->avatar_url ?? null,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'preferences' => $this->preferences ?? [],
            // 'membership' => $this->membership ?? null,
            // 'stats' => $this->stats ?? null,
            // 'menu' => $this->menu ?? null,
        ];
    }
}
