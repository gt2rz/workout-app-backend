<?php

namespace Tests\Concerns;

use App\Models\ApiKey;
use App\Models\User;

trait InteractsWithApi
{
    protected User $apiUser;

    protected ApiKey $apiKey;

    protected function setUpApiUser(?User $user = null): self
    {
        $this->apiUser = $user ?? User::factory()->create();
        $this->apiKey = ApiKey::generate('Test Key');

        $this->actingAs($this->apiUser, 'sanctum');
        $this->withHeaders(['X-API-KEY' => $this->apiKey->key]);

        return $this;
    }
}
