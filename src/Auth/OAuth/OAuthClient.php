<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

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
        return $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->configString('client_id'),
            'client_secret' => $this->configString('client_secret'),
            'redirect_uri' => $this->configString('redirect_uri'),
        ]);
    }

    public function refresh(string $refreshToken): TokenSet
    {
        try {
            return $this->postToken([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->configString('client_id'),
                'client_secret' => $this->configString('client_secret'),
            ]);
        } catch (ChatworkRequestException $e) {
            throw new ChatworkAuthenticationException('OAuth refresh failed.', previous: $e);
        }
    }

    /**
     * @param  array<string, string>  $params
     */
    private function postToken(array $params): TokenSet
    {
        $tokenUrl = $this->configString('token_url');

        $response = Http::asForm()->post($tokenUrl, $params);

        if ($response->failed()) {
            throw ChatworkRequestException::fromResponse(
                $response,
                'POST',
                $tokenUrl,
                'issueOAuthToken',
            );
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new \RuntimeException('OAuth token endpoint returned a non-JSON object body.');
        }

        /** @var array<string, mixed> $body */
        return TokenSet::fromArray($body);
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
