<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/contacts` エンドポイントグループの公開 API。
 */
final class ContactsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザーのコンタクト一覧を取得する (GET /contacts)。
     *
     * @return list<ContactData> デフォルト Dto モード時（204 は `[]` に縮退）；他の ResponseMode はそれぞれの型を返す
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function list(): mixed
    {
        return $this->client->sendList('GET', '/contacts', [], ContactData::class, 'listContacts');
    }
}
