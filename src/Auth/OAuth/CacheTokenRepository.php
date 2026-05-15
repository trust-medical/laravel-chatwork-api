<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

final class CacheTokenRepository implements TokenRepository
{
    private const KEY_PREFIX = 'chatwork:oauth:token:';

    public function __construct(private readonly CacheRepository $cache) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function save(TokenSet $tokenSet, array $context = []): void
    {
        $connection = $context['connection'] ?? null;
        if (! is_string($connection) || $connection === '') {
            throw new InvalidArgumentException('CacheTokenRepository::save requires non-empty $context["connection"].');
        }

        $this->cache->forever($this->cacheKey($connection), $tokenSet->toArray());
    }

    public function find(string $connectionName): ?TokenSet
    {
        $value = $this->cache->get($this->cacheKey($connectionName));

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return TokenSet::fromArray($value);
    }

    private function cacheKey(string $connectionName): string
    {
        return self::KEY_PREFIX . hash('sha256', $connectionName);
    }
}
