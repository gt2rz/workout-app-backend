<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\json;

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
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'bio' => $this->bio ?? '',
            'avatar' => $this->avatar_url ?? null,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'preferences' => $this->getPreference(),
            'membership' => $this->getMembership(),
            'stats' => $this->getStats(),

            // this is static for now
           'menu' => $this->getMenuOptions()
        ];
    }

    private function getPreference()
    {
        $prefs = [];
        if ($this->userPreferences && $this->userPreferences->preferences) {
            return is_array($this->userPreferences->preferences)
                ? $this->userPreferences->preferences
                : json_decode($this->userPreferences->preferences, true);
        }
        else {
            // Default preferences
            return [
                'notifications' => true,
                'dark_mode' => false,
            ];
        }
    }

    private function getMembership()
    {
        if ($this->user && $this->user->membership) {
            return $this->user->membership;
        }
        return null;
    }

    private function getStats()
    {
        // Implement stats retrieval logic here
        return [
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
        ];
    }

    private function getMenuOptions()
    {
        // Implement menu options retrieval logic here
        return [
            'show_edit_profile' => true,
            'show_change_password' => true,
            'show_membership' => true,
            'show_preferences' => true,
            'show_statistics' => true,
            'show_logout' => true,
            'show_delete_account' => false,
            'show_goals' => true,
            'show_routine_history' => true,
        ];
    }
}
