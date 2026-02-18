<?php

use App\Models\ApiKey;
use App\Models\User;

beforeEach(function () {
    $this->apiKey = ApiKey::generate('Test Key');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'device' => 'test-device',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email'],
            'meta' => ['access_token', 'token_type'],
        ]);
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
        'device' => 'test-device',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('login fails with non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password123',
        'device' => 'test-device',
    ]);

    $response->assertStatus(422);
});

test('login fails without device field', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(422);
});
