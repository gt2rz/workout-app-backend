<?php

use App\Models\ApiKey;
use App\Models\Profile;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('authenticated user can get their profile', function () {
    Profile::create([
        'user_id' => $this->user->id,
        'bio' => 'Test bio',
    ]);

    $response = $this->getJson('/api/v1/profile');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.bio', 'Test bio')
        ->assertJsonStructure([
            'data' => ['id', 'user_id', 'bio', 'avatar', 'preferences', 'membership', 'stats', 'menu'],
        ]);
});

test('returns 404 when profile does not exist', function () {
    $response = $this->getJson('/api/v1/profile');

    $response->assertStatus(404)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('code', 'PROFILE.NOT_FOUND');
});
