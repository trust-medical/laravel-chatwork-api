<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

/**
 * Chatwork の認証ヘッダーをリクエストに付与する認証戦略。
 *
 * 各実装は相互排他的な認証方式（API token または OAuth2 Bearer）のいずれか一方を表す。
 * 1 つのリクエストに `x-chatworktoken` と `Authorization: Bearer` の両方を同時に付与してはならないため、
 * 呼び出し側はリクエストごとにどちらか一方の Credentials 実装を選択する。
 */
interface Credentials
{
    /**
     * この認証情報の認証ヘッダーを送信リクエストに適用する。
     *
     * 実装は自身のヘッダーのみを設定し、他の認証ヘッダーは変更しないこと。
     * これにより単一認証情報の不変条件を維持する。
     */
    public function applyTo(PendingRequest $request): PendingRequest;
}
