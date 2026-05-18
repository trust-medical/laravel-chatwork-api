<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

final readonly class ApiTokenCredentials implements Credentials
{
    public function __construct(private string $token) {}

    /**
     * API token を `x-chatworktoken` ヘッダーとしてリクエストに付与する。
     */
    public function applyTo(PendingRequest $request): PendingRequest
    {
        return $request->withHeaders(['x-chatworktoken' => $this->token]);
    }

    /**
     * var_dump / dd でトークンが平文露出しないようマスクする。
     *
     * @return array{token: string}
     */
    public function __debugInfo(): array
    {
        return ['token' => '***redacted***'];
    }
}
