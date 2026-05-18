<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;

/**
 * Chatwork の認証情報をオンデマンドで解決するプロバイダー。
 *
 * 静的な設定値の代わりに、DB・シークレットマネージャー・更新済み OAuth2 トークンなど
 * 動的なソースから認証情報を遅延取得できるようにする。
 * ローテーションや更新後のトークンを確実に反映するため、実装はステートレスにし、
 * 呼び出しごとに解決を行うこと。
 */
interface TokenProvider
{
    /**
     * 次のリクエストに使用する認証情報を解決する。
     *
     * @throws ChatworkAuthenticationException 認証情報を解決できない場合
     */
    public function credentials(): Credentials;
}
