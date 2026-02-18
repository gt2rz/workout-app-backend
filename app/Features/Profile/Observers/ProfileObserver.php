<?php

namespace App\Features\Profile\Observers;

use App\Models\Profile;
use Illuminate\Support\Facades\Cache;

class ProfileObserver
{
    public function updated(Profile $profile): void
    {
        $this->clearCache($profile);
    }

    public function deleted(Profile $profile): void
    {
        $this->clearCache($profile);
    }

    private function clearCache(Profile $profile): void
    {
        Cache::forget("profile:user:{$profile->user_id}");
        Cache::forget("home:user:{$profile->user_id}:date:".today()->toDateString());
    }
}
