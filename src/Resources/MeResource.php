<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class MeResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function get(): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s)', $this->client::class));
    }
}
