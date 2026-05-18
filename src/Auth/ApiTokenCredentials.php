<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

final readonly class ApiTokenCredentials implements Credentials
{
    public function __construct(public string $token) {}

    /**
     * API token を `x-chatworktoken` ヘッダーとしてリクエストに付与する。
     */
    public function applyTo(PendingRequest $request): PendingRequest
    {
        return $request->withHeaders(['x-chatworktoken' => $this->token]);
    }
}
