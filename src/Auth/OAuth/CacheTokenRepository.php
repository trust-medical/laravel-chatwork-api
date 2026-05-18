<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use InvalidArgumentException;

/**
 * キャッシュバックエンドを用いた永続化 OAuth2 トークンストア。
 *
 * リクエストをまたいでもキューワーカーをまたいでも存続するため、本番環境向けのデフォルト実装。
 * connection 名の SHA-256 ハッシュをキーとすることで、connection 識別子が
 * キャッシュキーやバックエンドログに漏洩しない。
 */
final class CacheTokenRepository implements TokenRepository
{
    private const KEY_PREFIX = 'chatwork:oauth:token:';

    public function __construct(private readonly CacheRepository $cache) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws InvalidArgumentException $context['connection'] が存在しないか空でない文字列でない場合。
     */
    public function save(TokenSet $tokenSet, array $context = []): void
    {
        $connection = $context['connection'] ?? null;
        if (! is_string($connection) || $connection === '') {
            throw new InvalidArgumentException('CacheTokenRepository::save requires non-empty $context["connection"].');
        }

        $this->cache->forever($this->cacheKey($connection), $tokenSet->toArray());
    }

    public function find(string $connectionName): ?TokenSet
    {
        $value = $this->cache->get($this->cacheKey($connectionName));

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return TokenSet::fromArray($value);
    }

    private function cacheKey(string $connectionName): string
    {
        return self::KEY_PREFIX . hash('sha256', $connectionName);
    }
}
