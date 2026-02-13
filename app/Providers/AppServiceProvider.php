<?php

namespace App\Providers;

use App\Models\WorkoutSession;
use App\Observers\WorkoutSessionObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->isLocal()) {
            URL::forceScheme('https');
        }

        WorkoutSession::observe(WorkoutSessionObserver::class);
    }
}
