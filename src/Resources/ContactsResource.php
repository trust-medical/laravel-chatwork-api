<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class ContactsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s)', $this->client::class));
    }
}
