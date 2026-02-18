<?php

test('health check returns status up', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(200)
        ->assertJsonPath('status', 'up')
        ->assertJsonStructure([
            'status',
            'data' => [
                'service',
                'version',
                'php_version',
                'environment',
                'checks' => ['database', 'cache'],
            ],
            'timestamp',
        ]);
});

test('health check includes correct service name', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertJsonPath('data.service', 'Workout App API');
});
