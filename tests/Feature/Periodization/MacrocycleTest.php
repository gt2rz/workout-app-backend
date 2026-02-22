<?php

use App\Models\ApiKey;
use App\Models\Macrocycle;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('index retorna solo los macrociclos del usuario autenticado', function () {
    Macrocycle::factory()->count(2)->create(['user_id' => $this->user->id]);
    Macrocycle::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/periodization/macrocycles');

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(2, 'data');
});

test('store crea un macrociclo y retorna 201', function () {
    $payload = [
        'name' => 'Macrociclo Fuerza',
        'goal' => 'Aumentar fuerza máxima',
        'start_date' => '2026-03-01',
        'end_date' => '2026-09-01',
        'duration_weeks' => 26,
    ];

    $response = $this->postJson('/api/v1/periodization/macrocycles', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Macrociclo Fuerza')
        ->assertJsonPath('data.duration_weeks', 26);

    expect(Macrocycle::where('user_id', $this->user->id)->count())->toBe(1);
});

test('store retorna 422 si faltan campos requeridos', function () {
    $response = $this->postJson('/api/v1/periodization/macrocycles', []);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('show retorna el macrociclo correcto', function () {
    $macrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$macrocycle->id}");

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $macrocycle->id)
        ->assertJsonPath('data.name', $macrocycle->name);
});

test('update modifica el macrociclo', function () {
    $macrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);

    $response = $this->putJson("/api/v1/periodization/macrocycles/{$macrocycle->id}", [
        'name' => 'Nombre actualizado',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Nombre actualizado');

    expect($macrocycle->fresh()->name)->toBe('Nombre actualizado');
});

test('destroy elimina el macrociclo y retorna 204', function () {
    $macrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/v1/periodization/macrocycles/{$macrocycle->id}");

    $response->assertStatus(204);
    expect(Macrocycle::find($macrocycle->id))->toBeNull();
});

test('otro usuario recibe 403 al intentar ver un macrociclo ajeno', function () {
    $otherUser = User::factory()->create();
    $macrocycle = Macrocycle::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$macrocycle->id}");

    $response->assertForbidden();
});

test('otro usuario recibe 403 al intentar actualizar un macrociclo ajeno', function () {
    $otherUser = User::factory()->create();
    $macrocycle = Macrocycle::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->putJson("/api/v1/periodization/macrocycles/{$macrocycle->id}", [
        'name' => 'Intento de hackeo',
    ]);

    $response->assertForbidden();
});

test('otro usuario recibe 403 al intentar eliminar un macrociclo ajeno', function () {
    $otherUser = User::factory()->create();
    $macrocycle = Macrocycle::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->deleteJson("/api/v1/periodization/macrocycles/{$macrocycle->id}");

    $response->assertForbidden();
});

test('request sin API key recibe 401', function () {
    $response = $this->getJson('/api/v1/periodization/macrocycles', [
        'X-API-KEY' => '',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH.INVALID_API_KEY');
});
