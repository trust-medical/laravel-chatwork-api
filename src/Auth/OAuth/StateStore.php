<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

/**
 * OAuth2 CSRF `state` 値とそれに紐付くペイロードの単回限り利用ストア。
 */
interface StateStore
{
    /**
     * state トークンをキーとして、有効期限付きでペイロードを永続化。
     *
     * @param  array<string, mixed>  $payload
     */
    public function put(string $state, array $payload, int $ttlSeconds): void;

    /**
     * state をアトミックに消費してペイロードを返す。未知または期限切れの場合は null。
     *
     * 実装は読み取り時にエントリを必ず削除し、各 state を最大1回しか受理しないこと（リプレイ保護）。
     *
     * @return array<string, mixed>|null
     */
    public function pull(string $state): ?array;
}
