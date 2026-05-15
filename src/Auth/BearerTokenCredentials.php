<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

final readonly class BearerTokenCredentials implements Credentials
{
    public function __construct(public string $token) {}

    public function applyTo(PendingRequest $request): PendingRequest
    {
        return $request->withToken($this->token);
    }
}
