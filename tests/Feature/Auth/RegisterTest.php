<?php

use App\Models\ApiKey;
use App\Models\User;

beforeEach(function () {
    $this->apiKey = ApiKey::generate('Test Key');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('user can register with valid data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.name', 'Test User')
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'initials', 'registered_at'],
            'meta' => ['access_token', 'token_type'],
        ]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('register fails with missing fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['errors' => ['name', 'email', 'password']]);
});

test('register fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('register fails with short password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertStatus(422);
});
