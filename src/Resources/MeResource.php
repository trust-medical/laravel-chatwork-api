<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyAccountData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/me` エンドポイントの公開 API。
 *
 * @api 公開 API。宣言戻り値型は asDto() モード契約（他モードは実行時型が変わる。型対応表は README / docs を参照）。
 */
final class MeResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザー自身のアカウントプロフィールを取得する (GET /me)。
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function get(): mixed
    {
        return $this->client->send('GET', '/me', [], MyAccountData::class, 'getMe');
    }
}
