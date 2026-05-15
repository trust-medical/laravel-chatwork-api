<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class CacheStateStore implements StateStore
{
    private const KEY_PREFIX = 'chatwork:oauth:state:';

    public function __construct(private readonly CacheRepository $cache) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $state, array $payload, int $ttlSeconds): void
    {
        $this->cache->put($this->cacheKey($state), $payload, $ttlSeconds);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pull(string $state): ?array
    {
        $value = $this->cache->pull($this->cacheKey($state));

        return is_array($value) ? $value : null;
    }

    private function cacheKey(string $state): string
    {
        return self::KEY_PREFIX . $state;
    }
}
