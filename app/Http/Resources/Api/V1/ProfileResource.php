<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'preferences' => [
                'notifications' => $this->preferences['notifications'] ?? true,
                'dark_mode' => $this->preferences['dark_mode'] ?? false,
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
                        2025-09 => '105 kg',
                        2025-10 => '104.5 kg',
                        2025-11 => '104 kg',
                        2025-12 => '103.8 kg',
                        2026-01 => '103.5 kg'
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
    }
}
