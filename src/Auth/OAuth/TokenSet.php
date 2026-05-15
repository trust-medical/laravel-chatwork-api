<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use DateTimeImmutable;

final readonly class TokenSet
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public DateTimeImmutable $expiresAt,
        public string $tokenType = 'Bearer',
    ) {}

    public function isExpired(int $leewaySeconds = 0): bool
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (leeway=%d)', $leewaySeconds));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (keys=%s)', implode(',', array_keys($data))));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
