<?php

namespace App\Providers;

use App\Features\Profile\Observers\ProfileObserver;
use App\Features\Tracking\Observers\UserWeightObserver;
use App\Features\Workout\Observers\WorkoutSessionObserver;
use App\Models\Profile;
use App\Models\UserWeight;
use App\Models\WorkoutSession;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        Profile::observe(ProfileObserver::class);
        UserWeight::observe(UserWeightObserver::class);

        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 500) {
                Log::channel('api')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'time_ms' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }
}
