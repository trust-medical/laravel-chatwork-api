<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class IncomingRequestsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s)', $this->client::class));
    }

    public function accept(int $requestId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (requestId=%d)', $requestId));
    }

    public function decline(int $requestId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (requestId=%d)', $requestId));
    }
}
