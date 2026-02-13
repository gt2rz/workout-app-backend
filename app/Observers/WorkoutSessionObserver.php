<?php

namespace App\Observers;

use App\Models\WorkoutSession;
use App\Services\Workout\WorkoutTodayService;

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
