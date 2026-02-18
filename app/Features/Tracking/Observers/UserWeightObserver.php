<?php

namespace App\Features\Tracking\Observers;

use App\Models\UserWeight;
use Illuminate\Support\Facades\Cache;

class UserWeightObserver
{
    public function created(UserWeight $userWeight): void
    {
        $this->clearCache($userWeight);
    }

    public function updated(UserWeight $userWeight): void
    {
        $this->clearCache($userWeight);
    }

    public function deleted(UserWeight $userWeight): void
    {
        $this->clearCache($userWeight);
    }

    private function clearCache(UserWeight $userWeight): void
    {
        Cache::forget("weight:history:user:{$userWeight->user_id}");
        Cache::forget("home:user:{$userWeight->user_id}:date:".today()->toDateString());
    }
}
