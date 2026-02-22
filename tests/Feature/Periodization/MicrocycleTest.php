<?php

use App\Models\ApiKey;
use App\Models\Macrocycle;
use App\Models\Mesocycle;
use App\Models\MesocycleType;
use App\Models\Microcycle;
use App\Models\SplitType;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);

    $this->macrocycle = Macrocycle::factory()->create(['user_id' => $this->user->id]);
    $mesocycleType = MesocycleType::factory()->create();
    $splitType = SplitType::factory()->create();
    $this->mesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $this->macrocycle->id,
        'mesocycle_type_id' => $mesocycleType->id,
        'split_type_id' => $splitType->id,
    ]);
});

test('index lista los microciclos del mesociclo', function () {
    Microcycle::factory()->count(4)->sequence(fn ($seq) => [
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => $seq->index + 1,
        'start_date' => now()->addWeeks($seq->index)->startOfWeek(),
        'end_date' => now()->addWeeks($seq->index)->endOfWeek(),
    ])->create();

    $response = $this->getJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles"
    );

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonCount(4, 'data');
});

test('store crea un microciclo bajo el mesociclo', function () {
    $payload = [
        'week_number' => 1,
        'start_date' => '2026-03-02',
        'end_date' => '2026-03-08',
        'is_deload' => false,
        'target_volume_percentage' => 100,
        'status' => 'planned',
    ];

    $response = $this->postJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles",
        $payload
    );

    $response->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.mesocycle_id', $this->mesocycle->id)
        ->assertJsonPath('data.week_number', 1)
        ->assertJsonPath('data.is_deload', false);
});

test('store retorna 422 con datos inválidos', function () {
    $response = $this->postJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles",
        []
    );

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('show retorna el microciclo correcto', function () {
    $microcycle = Microcycle::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
    ]);

    $response = $this->getJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles/{$microcycle->id}"
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $microcycle->id)
        ->assertJsonPath('data.week_number', 1);
});

test('update modifica el microciclo', function () {
    $microcycle = Microcycle::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
    ]);

    $response = $this->putJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles/{$microcycle->id}",
        ['status' => 'active', 'target_volume_percentage' => 80]
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.target_volume_percentage', 80);
});

test('destroy elimina el microciclo y retorna 204', function () {
    $microcycle = Microcycle::factory()->create([
        'mesocycle_id' => $this->mesocycle->id,
        'week_number' => 1,
    ]);

    $response = $this->deleteJson(
        "/api/v1/periodization/macrocycles/{$this->macrocycle->id}/mesocycles/{$this->mesocycle->id}/microcycles/{$microcycle->id}"
    );

    $response->assertStatus(204);
    expect(Microcycle::find($microcycle->id))->toBeNull();
});

test('otro usuario recibe 403 al acceder al macrociclo ajeno', function () {
    $otherUser = User::factory()->create();
    $otherMacrocycle = Macrocycle::factory()->create(['user_id' => $otherUser->id]);
    $mesocycleType = MesocycleType::factory()->create();
    $splitType = SplitType::factory()->create();
    $otherMesocycle = Mesocycle::factory()->create([
        'macrocycle_id' => $otherMacrocycle->id,
        'mesocycle_type_id' => $mesocycleType->id,
        'split_type_id' => $splitType->id,
    ]);

    $response = $this->getJson(
        "/api/v1/periodization/macrocycles/{$otherMacrocycle->id}/mesocycles/{$otherMesocycle->id}/microcycles"
    );

    $response->assertForbidden();
});
