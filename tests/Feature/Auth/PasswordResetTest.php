<?php

use App\Models\ApiKey;
use App\Models\User;

beforeEach(function () {
    $this->apiKey = ApiKey::generate('Test Key');
    $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);
});

test('forgot password sends reset link for valid email', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success');
});

test('forgot password fails for non-existent email', function () {
    $response = $this->postJson('/api/v1/auth/password/forgot', [
        'email' => 'nonexistent@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('change password works for authenticated user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('oldpassword'),
    ]);
    $this->actingAs($user, 'sanctum');

    $response = $this->postJson('/api/v1/auth/password/change', [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success');
});

test('change password fails with wrong current password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('oldpassword'),
    ]);
    $this->actingAs($user, 'sanctum');

    $response = $this->postJson('/api/v1/auth/password/change', [
        'current_password' => 'wrongpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertStatus(422);
});
