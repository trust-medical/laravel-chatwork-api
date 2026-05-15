<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

final readonly class ApiTokenCredentials implements Credentials
{
    public function __construct(public string $token) {}

    public function applyTo(PendingRequest $request): PendingRequest
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (request=%s)', $request::class));
    }
}
