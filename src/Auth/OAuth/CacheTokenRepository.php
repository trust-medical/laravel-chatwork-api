<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use InvalidArgumentException;

/**
 * キャッシュバックエンドを用いた永続化 OAuth2 トークンストア。
 *
 * リクエストをまたいでもキューワーカーをまたいでも存続するため、本番環境向けのデフォルト実装。
 * connection 名の SHA-256 ハッシュをキーとすることで、connection 識別子が
 * キャッシュキーやバックエンドログに漏洩しない。
 * access/refresh トークンは Laravel Encrypter で暗号化して保存するため、
 * Redis / Memcached を直接読まれてもトークンは平文露出しない。暗号化前の
 * 平文エントリや復号不能な値は「存在しない」とみなす（再認証へ誘導）。
 */
final class CacheTokenRepository implements TokenRepository
{
    private const KEY_PREFIX = 'chatwork:oauth:token:';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly Encrypter $encrypter,
    ) {}

    public function save(string $connectionName, TokenSet $tokenSet): void
    {
        $this->cache->forever(
            $this->cacheKey($connectionName),
            $this->encrypter->encrypt($tokenSet->toArray()),
        );
    }

    public function find(string $connectionName): ?TokenSet
    {
        $value = $this->cache->get($this->cacheKey($connectionName));

        if (! is_string($value)) {
            return null;
        }

        try {
            $decrypted = $this->encrypter->decrypt($value);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($decrypted)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $decrypted */
            return TokenSet::fromArray($decrypted);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function cacheKey(string $connectionName): string
    {
        return self::KEY_PREFIX . hash('sha256', $connectionName);
    }
}
