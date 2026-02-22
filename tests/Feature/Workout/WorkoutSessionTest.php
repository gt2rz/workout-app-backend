<?php

use App\Models\ApiKey;
use App\Models\User;
use App\Models\WorkoutSession;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('index retorna las sesiones paginadas del usuario autenticado', function () {
    WorkoutSession::factory()->count(3)->create(['user_id' => $this->user->id]);
    WorkoutSession::factory()->count(2)->create();

    $response = $this->getJson('/api/v1/workouts/sessions');

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data');
});

test('index filtra por status correctamente', function () {
    WorkoutSession::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);
    WorkoutSession::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'scheduled',
    ]);

    $response = $this->getJson('/api/v1/workouts/sessions?status=completed');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data');
});

test('index filtra por fecha correctamente', function () {
    WorkoutSession::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_date' => '2026-03-15',
    ]);
    WorkoutSession::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_date' => '2026-04-01',
    ]);

    $response = $this->getJson('/api/v1/workouts/sessions?date=2026-03-15');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

test('store crea una sesion de entrenamiento y retorna 201', function () {
    $payload = [
        'scheduled_date' => '2026-03-01',
        'status' => 'scheduled',
        'notes' => 'Entrenamiento de fuerza',
    ];

    $response = $this->postJson('/api/v1/workouts/sessions', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.scheduled_date', '2026-03-01')
        ->assertJsonPath('data.status', 'scheduled');

    expect(WorkoutSession::where('user_id', $this->user->id)->count())->toBe(1);
});

test('store retorna 422 si faltan campos requeridos', function () {
    $response = $this->postJson('/api/v1/workouts/sessions', []);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('show retorna la sesion correcta', function () {
    $session = WorkoutSession::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_date' => '2026-03-10',
    ]);

    $response = $this->getJson("/api/v1/workouts/sessions/{$session->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $session->id)
        ->assertJsonPath('data.scheduled_date', '2026-03-10');
});

test('update modifica la sesion de entrenamiento', function () {
    $session = WorkoutSession::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'scheduled',
    ]);

    $response = $this->putJson("/api/v1/workouts/sessions/{$session->id}", [
        'status' => 'completed',
        'duration_minutes' => 60,
        'overall_rpe' => 8,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.duration_minutes', 60)
        ->assertJsonPath('data.overall_rpe', 8);
});

test('destroy elimina la sesion y retorna 204', function () {
    $session = WorkoutSession::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/v1/workouts/sessions/{$session->id}");

    $response->assertStatus(204);
    expect(WorkoutSession::find($session->id))->toBeNull();
});

test('otro usuario recibe 403 al intentar ver una sesion ajena', function () {
    $otherUser = User::factory()->create();
    $session = WorkoutSession::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/v1/workouts/sessions/{$session->id}");

    $response->assertForbidden();
});

test('otro usuario recibe 403 al intentar actualizar una sesion ajena', function () {
    $otherUser = User::factory()->create();
    $session = WorkoutSession::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->putJson("/api/v1/workouts/sessions/{$session->id}", [
        'status' => 'completed',
    ]);

    $response->assertForbidden();
});

test('otro usuario recibe 403 al intentar eliminar una sesion ajena', function () {
    $otherUser = User::factory()->create();
    $session = WorkoutSession::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->deleteJson("/api/v1/workouts/sessions/{$session->id}");

    $response->assertForbidden();
});

test('request sin API key recibe 401', function () {
    $response = $this->getJson('/api/v1/workouts/sessions', [
        'X-API-KEY' => '',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.INVALID_API_KEY');
});
