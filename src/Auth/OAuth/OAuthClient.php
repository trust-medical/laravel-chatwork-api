<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

final class OAuthClient
{
    /**
     * @param  array<string, mixed>  $context
     * @param  array<int, string>|null  $scopes
     */
    public function buildAuthorizationUrl(array $context = [], ?array $scopes = null): string
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (context_keys=%s, scopes=%s)',
            implode(',', array_keys($context)),
            $scopes === null ? 'null' : implode(',', $scopes),
        ));
    }

    public function exchange(string $code): TokenSet
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (code_length=%d)', strlen($code)));
    }

    public function refresh(string $refreshToken): TokenSet
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (token_length=%d)', strlen($refreshToken)));
    }
}
