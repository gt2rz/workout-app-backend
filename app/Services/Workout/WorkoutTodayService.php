<?php

namespace App\Services\Workout;

use App\Models\User;
use App\Models\WorkoutSession;
use Illuminate\Cache\CacheManager;

class WorkoutTodayService
{
    public function __construct(
        private CacheManager $cache
    ) {}

    public function getWorkoutForToday(User $user): ?WorkoutSession
    {
        $cacheKey = $this->getCacheKey($user);

        return $this->cache->remember($cacheKey, 43200, function () use ($user) {
            return WorkoutSession::query()
                ->forUser($user)
                ->scheduledForToday()
                ->pending()
                ->withFullDetails()
                ->first();
        });
    }

    public function clearTodayCache(User $user): void
    {
        $cacheKey = $this->getCacheKey($user);
        $this->cache->forget($cacheKey);
    }

    public function hasWorkoutToday(User $user): bool
    {
        return $this->getWorkoutForToday($user) !== null;
    }

    private function getCacheKey(User $user): string
    {
        $today = today()->toDateString();

        return "workout:today:user:{$user->id}:date:{$today}";
    }
}
