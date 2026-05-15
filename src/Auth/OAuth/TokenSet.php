<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use DateTimeImmutable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

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
        return Carbon::now()->getTimestamp() + $leewaySeconds >= $this->expiresAt->getTimestamp();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['access_token', 'refresh_token'] as $key) {
            if (! isset($data[$key]) || ! is_string($data[$key])) {
                throw new InvalidArgumentException(sprintf('TokenSet::fromArray missing required key: %s', $key));
            }
        }

        $expiresAt = self::resolveExpiresAt($data);

        $tokenType = isset($data['token_type']) && is_string($data['token_type'])
            ? $data['token_type']
            : 'Bearer';

        return new self(
            accessToken: $data['access_token'],
            refreshToken: $data['refresh_token'],
            expiresAt: $expiresAt,
            tokenType: $tokenType,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt->format(DateTimeImmutable::ATOM),
            'token_type' => $this->tokenType,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveExpiresAt(array $data): DateTimeImmutable
    {
        if (isset($data['expires_at']) && is_string($data['expires_at'])) {
            $parsed = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $data['expires_at']);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        if (isset($data['expires_in']) && is_int($data['expires_in'])) {
            return Carbon::now()->addSeconds($data['expires_in'])->toDateTimeImmutable();
        }

        throw new InvalidArgumentException('TokenSet::fromArray requires either expires_at (ISO 8601) or expires_in (int seconds).');
    }
}
