<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

/**
 * connection 名をキーとする OAuth2 トークンセットの差し替え可能な永続化境界。
 *
 * @api この interface は公開拡張点であり、DB / シークレットマネージャ等の
 *   カスタム実装を `chatwork.oauth.token_repository` で差し替えできる。
 *   メソッドシグネチャは v1 で恒久固定される後方互換契約である。
 */
interface TokenRepository
{
    /**
     * connection 名をキーに OAuth2 トークンセットを永続化する。
     *
     * $connectionName は空でない文字列であること（呼び出し側責務）。
     */
    public function save(string $connectionName, TokenSet $tokenSet): void;

    /**
     * connection に保存されたトークンセットを返す。未保存の場合は null。
     */
    public function find(string $connectionName): ?TokenSet;
}
