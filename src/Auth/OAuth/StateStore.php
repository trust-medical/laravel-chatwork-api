<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

interface StateStore
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(string $state, array $payload, int $ttlSeconds): void;

    /**
     * @return array<string, mixed>|null
     */
    public function pull(string $state): ?array;
}
