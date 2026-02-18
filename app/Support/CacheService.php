<?php

namespace App\Support;

use Closure;
use Illuminate\Cache\CacheManager;

abstract class CacheService
{
    public function __construct(
        protected CacheManager $cache
    ) {}

    abstract protected function prefix(): string;

    abstract protected function defaultTtl(): int;

    protected function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return $this->cache->remember(
            $this->prefix().':'.$key,
            $ttl ?? $this->defaultTtl(),
            $callback
        );
    }

    protected function forget(string $key): void
    {
        $this->cache->forget($this->prefix().':'.$key);
    }

    protected function flush(string $pattern): void
    {
        $this->cache->forget($this->prefix().':'.$pattern);
    }
}
