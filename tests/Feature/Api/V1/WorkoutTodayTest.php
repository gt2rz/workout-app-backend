<?php

use App\Models\ApiKey;
use App\Models\Exercise;
use App\Models\ExerciseType;
use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSession;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('home endpoint returns workout scheduled for today', function () {
    $exerciseType = ExerciseType::firstOrCreate(['name' => 'Compound'], ['description' => 'Compound movements']);
    $exercise = Exercise::factory()->create(['exercise_type_id' => $exerciseType->id]);

    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    WorkoutExercise::factory()
        ->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
        ]);

    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200)
        ->assertJsonPath('workout_today.has_workout', true)
        ->assertJsonPath('workout_today.workout.id', $session->id)
        ->assertJsonStructure([
            'workout_today' => [
                'enabled',
                'has_workout',
                'workout' => [
                    'id',
                    'title',
                    'subtitle',
                    'duration_minutes',
                    'exercises_count',
                    'status',
                    'exercises',
                ],
            ],
        ]);
});

test('home endpoint returns no workout when none scheduled for today', function () {
    WorkoutSession::factory()
        ->create([
            'user_id' => $this->user->id,
            'scheduled_date' => today()->addDays(1),
        ]);

    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200);

    expect($response->json('workout_today.has_workout'))->toBeFalse();
    expect($response->json('workout_today.no_workout.title'))->toBe('¡Descanso hoy!');
});

test('home endpoint does not return completed workout', function () {
    WorkoutSession::factory()
        ->today()
        ->completed()
        ->create(['user_id' => $this->user->id]);

    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200);
    expect($response->json('workout_today.has_workout'))->toBeFalse();
});

test('home endpoint does not return skipped workout', function () {
    WorkoutSession::factory()
        ->today()
        ->create([
            'user_id' => $this->user->id,
            'status' => 'skipped',
        ]);

    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200);
    expect($response->json('workout_today.has_workout'))->toBeFalse();
});

test('workout today caches correctly', function () {
    $exerciseType = ExerciseType::firstOrCreate(['name' => 'Compound'], ['description' => 'Compound movements']);
    $exercise = Exercise::factory()->create(['exercise_type_id' => $exerciseType->id]);

    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    WorkoutExercise::factory()
        ->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);

    $cacheKey = "workout:today:user:{$this->user->id}:date:".today()->toDateString();

    expect(Cache::has($cacheKey))->toBeFalse();

    $this->getJson('/api/v1/home');

    expect(Cache::has($cacheKey))->toBeTrue();
});

test('cache is invalidated when workout is updated', function () {
    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $cacheKey = "workout:today:user:{$this->user->id}:date:".today()->toDateString();

    $this->getJson('/api/v1/home');
    expect(Cache::has($cacheKey))->toBeTrue();

    $session->update(['status' => 'completed']);

    expect(Cache::has($cacheKey))->toBeFalse();
});

test('workout formats duration in spanish', function () {
    $exerciseType = ExerciseType::firstOrCreate(['name' => 'Compound'], ['description' => 'Compound movements']);
    $exercise = Exercise::factory()->create(['exercise_type_id' => $exerciseType->id]);

    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create([
            'user_id' => $this->user->id,
            'duration_minutes' => 45,
        ]);

    WorkoutExercise::factory()
        ->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
        ]);

    $response = $this->getJson('/api/v1/home');

    $response->assertJsonPath('workout_today.workout.duration_minutes', '45 minutos');
});

test('workout formats exercises count in spanish', function () {
    $exerciseType = ExerciseType::firstOrCreate(['name' => 'Compound'], ['description' => 'Compound movements']);

    $session = WorkoutSession::factory()
        ->today()
        ->pending()
        ->create(['user_id' => $this->user->id]);

    $exercises = Exercise::factory()->count(3)->create(['exercise_type_id' => $exerciseType->id]);

    foreach ($exercises as $index => $exercise) {
        WorkoutExercise::factory()->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'order' => $index + 1,
        ]);
    }

    $response = $this->getJson('/api/v1/home');

    $response->assertJsonPath('workout_today.workout.exercises_count', '3 ejercicios');
});
