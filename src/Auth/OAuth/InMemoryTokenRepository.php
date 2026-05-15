<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use InvalidArgumentException;

/**
 * In-memory token store for tests and local development only.
 *
 * Tokens live in PHP process memory and are lost when the request ends, so
 * production deployments (multi-worker / queue workers) must use a persistent
 * implementation such as CacheTokenRepository or a custom database-backed one.
 */
final class InMemoryTokenRepository implements TokenRepository
{
    /** @var array<string, TokenSet> */
    private array $store = [];

    /**
     * @param  array<string, mixed>  $context
     */
    public function save(TokenSet $tokenSet, array $context = []): void
    {
        $connection = $context['connection'] ?? null;
        if (! is_string($connection) || $connection === '') {
            throw new InvalidArgumentException('InMemoryTokenRepository::save requires non-empty $context["connection"].');
        }

        $this->store[$connection] = $tokenSet;
    }

    public function find(string $connectionName): ?TokenSet
    {
        return $this->store[$connectionName] ?? null;
    }
}
