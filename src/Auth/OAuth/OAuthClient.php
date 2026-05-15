<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

final class OAuthClient
{
    private const STATE_TTL_SECONDS = 600;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly StateStore $stateStore,
        private readonly array $config,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>|null  $scopes
     */
    public function buildAuthorizationUrl(
        array $context = [],
        ?array $scopes = null,
        string $connectionName = 'default',
    ): string {
        $state = $this->generateState();

        $this->stateStore->put(
            state: $state,
            payload: ['connection' => $connectionName, 'context' => $context],
            ttlSeconds: self::STATE_TTL_SECONDS,
        );

        $query = [
            'client_id' => $this->configString('client_id'),
            'redirect_uri' => $this->configString('redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ];

        if ($scopes !== null) {
            $query['scope'] = implode(' ', $scopes);
        }

        return $this->configString('authorization_url') . '?' . http_build_query($query);
    }

    public function exchange(string $code): TokenSet
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (code_length=%d)', strlen($code)));
    }

    public function refresh(string $refreshToken): TokenSet
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (token_length=%d)', strlen($refreshToken)));
    }

    private function generateState(): string
    {
        return bin2hex(random_bytes(24));
    }

    private function configString(string $key): string
    {
        $value = $this->config[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new \RuntimeException(sprintf('chatwork.oauth.%s is not configured.', $key));
        }

        return $value;
    }
}
