<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/contacts` エンドポイントグループの公開 API。
 *
 * @api 公開 API。宣言戻り値型は asDto() モード契約（他モードは実行時型が変わる。型対応表は README / docs を参照）。
 */
final class ContactsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザーのコンタクト一覧を取得する (GET /contacts)。
     *
     * @return list<ContactData> asDto() 契約（204 は `[]` に縮退）。他モードは ResponseMode に従い実行時型が変わる
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function list(): mixed
    {
        return $this->client->sendList('GET', '/contacts', [], ContactData::class, 'listContacts');
    }
}
