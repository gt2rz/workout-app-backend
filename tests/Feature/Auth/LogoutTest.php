<?php

use App\Models\ApiKey;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('authenticated user can logout', function () {
    $token = $this->user->createToken('test-device');
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'Sesión cerrada');
});
