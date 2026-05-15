<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

interface TokenRepository
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function save(TokenSet $tokenSet, array $context = []): void;

    public function find(string $connectionName): ?TokenSet;
}
