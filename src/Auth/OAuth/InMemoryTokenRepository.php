<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

/**
 * テストおよびローカル開発専用のインメモリトークンストア。
 *
 * @internal 公開 API ではない。後方互換の保証対象外。
 *
 * トークンは PHP プロセスメモリ上に存在し、リクエスト終了時に失われる。
 * 本番環境（マルチワーカー / キューワーカー）では CacheTokenRepository か
 * カスタムのデータベースバックエンド実装を使用すること。testing / local
 * 以外の環境で生成された場合は E_USER_NOTICE で能動的に警告する。
 */
final class InMemoryTokenRepository implements TokenRepository
{
    /** @var array<string, TokenSet> */
    private array $store = [];

    public function __construct()
    {
        if (! function_exists('app')) {
            return;
        }

        try {
            if (app()->environment(['testing', 'local'])) {
                return;
            }
        } catch (\Throwable) {
            // コンテナ未起動 / Laravel 外利用では環境判定不能なので警告しない。
            return;
        }

        trigger_error(
            'InMemoryTokenRepository is for testing/local development only; '
            . 'configure CacheTokenRepository or a persistent TokenRepository in production.',
            E_USER_NOTICE,
        );
    }

    public function save(string $connectionName, TokenSet $tokenSet): void
    {
        $this->store[$connectionName] = $tokenSet;
    }

    public function find(string $connectionName): ?TokenSet
    {
        return $this->store[$connectionName] ?? null;
    }
}
