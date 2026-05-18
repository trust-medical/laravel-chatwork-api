<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

/**
 * connection 名をキーとする OAuth2 トークンセットの差し替え可能な永続化境界。
 */
interface TokenRepository
{
    /**
     * $context['connection'] に指定された connection 名のトークンセットを永続化。
     *
     * @param  array<string, mixed>  $context  'connection' キーに空でない文字列を含むこと。
     *
     * @throws \InvalidArgumentException $context['connection'] が存在しないか空でない文字列でない場合。
     */
    public function save(TokenSet $tokenSet, array $context = []): void;

    /**
     * connection に保存されたトークンセットを返す。未保存の場合は null。
     */
    public function find(string $connectionName): ?TokenSet;
}
