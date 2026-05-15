<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class MyResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function status(): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s)', $this->client::class));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function tasks(array $filters = []): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (filters=%s)', implode(',', array_keys($filters))));
    }
}
