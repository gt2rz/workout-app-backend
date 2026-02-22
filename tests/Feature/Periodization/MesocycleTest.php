<?php

use App\Models\ApiKey;
use App\Models\Macrocycle;
use App\Models\Mesocycle;
use App\Models\MesocycleType;
use App\Models\SplitType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);

    $this->macrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);
    $this->mesocycleType = MesocycleType::factory()->create();
    $this->splitType = SplitType::factory()->create();
});

test('index lista los mesociclos del macrociclo del usuario', function () {
    Mesocycle::factory()->count(3)->create([
        'macrocycle_id' => $this->macrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles");

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(3, 'data');
});

test('store crea un mesociclo bajo el macrociclo', function () {
    $payload = [
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
        'order' => 1,
        'start_week' => 1,
        'duration_weeks' => 6,
    ];

    $response = $this->postJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles", $payload);

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.macrocycle_id', $this->macrocycle->id)
        ->assertJsonPath('data.order', 1)
        ->assertJsonPath('data.duration_weeks', 6);
});

test('store retorna 422 con datos inválidos', function () {
    $response = $this->postJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles", []);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('show retorna el mesociclo correcto', function () {
    $mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $this->macrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$mesocycle->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $mesocycle->id);
});

test('update modifica el mesociclo', function () {
    $mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $this->macrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->putJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$mesocycle->id}", [
        'duration_weeks' => 8,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.duration_weeks', 8);

    expect($mesocycle->fresh()->duration_weeks)->toBe(8);
});

test('destroy elimina el mesociclo y retorna 204', function () {
    $mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $this->macrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->deleteJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$mesocycle->id}");

    $response->assertStatus(204);
    expect(Mesocycle::find($mesocycle->id))->toBeNull();
});

test('otro usuario recibe 403 al acceder al macrociclo ajeno', function () {
    $otherUser = User::factory()->create();
    $otherMacrocycle = Macrocycle::factory()->create(['user_id' => $otherUser->id]);
    $mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $otherMacrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$otherMacrocycle->id}/mesocycles");

    $response->assertForbidden();
});

test('mesociclo de otro macrociclo retorna 404 por scope', function () {
    $otherMacrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);
    $mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $otherMacrocycle->id,
        'mesocycle_type_id' => $this->mesocycleType->id,
        'split_type_id' => $this->splitType->id,
    ]);

    $response = $this->getJson("/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$mesocycle->id}");

    $response->assertNotFound();
});
