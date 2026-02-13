<?php

use App\Models\User;
use App\Models\WorkoutSession;
use App\Services\Workout\WorkoutTodayService;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = app(WorkoutTodayService::class);
    $this->user = User::factory()->create();
});

test('generates correct cache key', function () {
    $reflection = new ReflectionClass($this->service);
    $method = $reflection->getMethod('getCacheKey');
    $method->setAccessible(true);

    $cacheKey = $method->invoke($this->service, $this->user);

    $expectedKey = "workout:today:user:{$this->user->id}:date:".today()->toDateString();

    expect($cacheKey)->toBe($expectedKey);
});

test('returns null when no workout scheduled for today', function () {
    $workout = $this->service->getWorkoutForToday($this->user);

    expect($workout)->toBeNull();
});

test('returns workout scheduled for today', function () {
    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $workout = $this->service->getWorkoutForToday($this->user);

    expect($workout)->not->toBeNull()
        ->and($workout->id)->toBe($session->id);
});

test('does not return completed workout', function () {
    WorkoutSession::factory()
        ->today()
        ->completed()
        ->create(['user_id' => $this->user->id]);

    $workout = $this->service->getWorkoutForToday($this->user);

    expect($workout)->toBeNull();
});

test('clears cache for specific user', function () {
    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $cacheKey = "workout:today:user:{$this->user->id}:date:".today()->toDateString();

    $this->service->getWorkoutForToday($this->user);
    expect(Cache::has($cacheKey))->toBeTrue();

    $this->service->clearTodayCache($this->user);
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('checks if user has workout today returns true', function () {
    WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $hasWorkout = $this->service->hasWorkoutToday($this->user);

    expect($hasWorkout)->toBeTrue();
});

test('checks if user has workout today returns false', function () {
    $hasWorkout = $this->service->hasWorkoutToday($this->user);

    expect($hasWorkout)->toBeFalse();
});

test('caches workout for 12 hours', function () {
    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $cacheKey = "workout:today:user:{$this->user->id}:date:".today()->toDateString();

    $this->service->getWorkoutForToday($this->user);

    $ttl = Cache::getStore()->getRedis()->ttl($cacheKey);

    expect($ttl)->toBeGreaterThan(43000)
        ->and($ttl)->toBeLessThanOrEqual(43200);
})->skip('Requires Redis for TTL checking');
