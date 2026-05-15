<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\PendingRequest;
use TrustMedical\LaravelChatworkApi\Connection;

final class ChatworkPendingRequestFactory
{
    public function create(Connection $connection): PendingRequest
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (connection=%s)', $connection->name));
    }
}
