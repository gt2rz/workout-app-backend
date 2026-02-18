<?php

use App\Models\ApiKey;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->apiKey = ApiKey::generate('Test Key');
    $this->actingAs($this->user, 'sanctum');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('authenticated user can access home endpoint', function () {
    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [
                'greeting',
                'weekly_overview',
                'workout_today',
                'progress_overview',
                'quick_access',
            ],
        ]);
});

test('home endpoint returns greeting with user name', function () {
    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200);

    $firstName = explode(' ', $this->user->name)[0];
    expect($response->json('data.greeting.greeting.user_name'))->toBe($firstName);
});

test('home endpoint returns weekly overview', function () {
    $response = $this->getJson('/api/v1/home');

    $response->assertStatus(200);

    $weekDays = $response->json('data.weekly_overview.week_days');
    expect($weekDays)->toBeArray()->toHaveCount(7);
});

test('request without api key returns 401', function () {
    $response = $this->withHeaders([
        'X-API-KEY' => 'invalid-key',
    ])->getJson('/api/v1/home');

    $response->assertStatus(401)
        ->assertJsonPath('code', 'AUTH.INVALID_API_KEY');
});
