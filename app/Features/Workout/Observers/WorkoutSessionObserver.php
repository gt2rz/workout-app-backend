<?php

namespace App\Features\Workout\Observers;

use App\Features\Workout\Services\WorkoutTodayService;
use App\Models\WorkoutSession;

class WorkoutSessionObserver
{
    public function __construct(
        private WorkoutTodayService $workoutTodayService
    ) {}

    public function created(WorkoutSession $workoutSession): void
    {
        $this->clearCache($workoutSession);
    }

    public function updated(WorkoutSession $workoutSession): void
    {
        $this->clearCache($workoutSession);
    }

    public function deleted(WorkoutSession $workoutSession): void
    {
        $this->clearCache($workoutSession);
    }

    private function clearCache(WorkoutSession $workoutSession): void
    {
        if ($workoutSession->user) {
            $this->workoutTodayService->clearTodayCache($workoutSession->user);
        }
    }
}
