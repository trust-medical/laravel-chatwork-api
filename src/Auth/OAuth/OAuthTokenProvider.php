<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Support\Facades\Cache;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\Credentials;
use TrustMedical\LaravelChatworkApi\Auth\TokenProvider;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;

final class OAuthTokenProvider implements TokenProvider
{
    private const LOCK_KEY_PREFIX = 'chatwork:oauth:refresh:';

    private const LOCK_TTL_SECONDS = 10;

    private const RETRY_DELAY_MICROSECONDS = 500_000;

    public function __construct(
        private readonly string $connectionName,
        private readonly TokenRepository $repository,
        private readonly OAuthClient $oauth,
        private readonly int $leewaySeconds = 60,
    ) {}

    public function credentials(): Credentials
    {
        $tokenSet = $this->repository->find($this->connectionName);
        if ($tokenSet === null) {
            throw new ChatworkAuthenticationException(
                sprintf('No OAuth TokenSet stored for connection "%s".', $this->connectionName),
            );
        }

        if (! $tokenSet->isExpired($this->leewaySeconds)) {
            return new BearerTokenCredentials($tokenSet->accessToken);
        }

        return new BearerTokenCredentials($this->coalescedRefresh($tokenSet)->accessToken);
    }

    private function coalescedRefresh(TokenSet $current): TokenSet
    {
        $lock = Cache::lock($this->lockKey(), self::LOCK_TTL_SECONDS);

        if (! $lock->get()) {
            usleep(self::RETRY_DELAY_MICROSECONDS);
            $latest = $this->repository->find($this->connectionName);
            if ($latest === null || $latest->isExpired($this->leewaySeconds)) {
                throw new ChatworkAuthenticationException(
                    sprintf('OAuth refresh contention for connection "%s" and stored token is still expired.', $this->connectionName),
                );
            }

            return $latest;
        }

        try {
            $latest = $this->repository->find($this->connectionName);
            if ($latest !== null && ! $latest->isExpired($this->leewaySeconds)) {
                return $latest;
            }

            $newTokenSet = $this->oauth->refresh($current->refreshToken);
            $this->repository->save($newTokenSet, ['connection' => $this->connectionName]);

            return $newTokenSet;
        } finally {
            $lock->release();
        }
    }

    private function lockKey(): string
    {
        return self::LOCK_KEY_PREFIX . hash('sha256', $this->connectionName);
    }
}
