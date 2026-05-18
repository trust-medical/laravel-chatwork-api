<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use InvalidArgumentException;

/**
 * テストおよびローカル開発専用のインメモリトークンストア。
 *
 * トークンは PHP プロセスメモリ上に存在し、リクエスト終了時に失われる。
 * 本番環境（マルチワーカー / キューワーカー）では CacheTokenRepository か
 * カスタムのデータベースバックエンド実装を使用すること。
 */
final class InMemoryTokenRepository implements TokenRepository
{
    /** @var array<string, TokenSet> */
    private array $store = [];

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws InvalidArgumentException $context['connection'] が存在しないか空でない文字列でない場合。
     */
    public function save(TokenSet $tokenSet, array $context = []): void
    {
        $connection = $context['connection'] ?? null;
        if (! is_string($connection) || $connection === '') {
            throw new InvalidArgumentException('InMemoryTokenRepository::save requires non-empty $context["connection"].');
        }

        $this->store[$connection] = $tokenSet;
    }

    public function find(string $connectionName): ?TokenSet
    {
        return $this->store[$connectionName] ?? null;
    }
}
