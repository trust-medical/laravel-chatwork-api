<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

final readonly class BearerTokenCredentials implements Credentials
{
    public function __construct(private string $token) {}

    /**
     * OAuth2 トークンを `Authorization: Bearer` ヘッダーとしてリクエストに付与する。
     */
    public function applyTo(PendingRequest $request): PendingRequest
    {
        return $request->withToken($this->token);
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
