<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use DateTimeImmutable;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * イミュータブルな OAuth2 トークンセット: access/refresh トークンと絶対有効期限を保持。
 */
final readonly class TokenSet
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public DateTimeImmutable $expiresAt,
        public string $tokenType = 'Bearer',
    ) {}

    /**
     * 現在時刻（＋leeway）が expiresAt 以上であれば true を返す。
     *
     * @param  int  $leewaySeconds  有効期限の何秒前から「期限切れ」とみなすか（プロアクティブ refresh 用）。
     */
    public function isExpired(int $leewaySeconds = 0): bool
    {
        return Carbon::now()->getTimestamp() + $leewaySeconds >= $this->expiresAt->getTimestamp();
    }

    /**
     * トークンエンドポイントレスポンスまたは永続化スナップショットから構築。
     *
     * 有効期限は `expires_at`（ISO 8601）が存在する場合はそちらを優先し、
     * 存在しない場合は `expires_in`（現在からの秒数）から導出する。
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidArgumentException access_token/refresh_token が存在しない場合、または expires_at・expires_in のいずれも利用できない場合。
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
     * @return array{access_token: string, refresh_token: string, expires_at: string, token_type: string}
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
     *
     * @throws InvalidArgumentException パース可能な expires_at も整数の expires_in も存在しない場合。
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
