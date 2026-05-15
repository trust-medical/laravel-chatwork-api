<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use InvalidArgumentException;

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
