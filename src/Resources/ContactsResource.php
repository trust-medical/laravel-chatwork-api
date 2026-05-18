<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
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
        $path = '/contacts';

        // GET /contacts は 200 で array<Contact>、204 で空ボディを返す
        // （仕様上 204 を宣言する唯一のリスト系エンドポイント）。
        // Dto モードを Collection 経由でルーティングすることで、204 も []
        // に正しく縮退する。他のモードは ChatworkClient::send をそのまま通す。
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                [],
                ContactData::class,
                'listContacts',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, [], ContactData::class, 'listContacts');
    }
}
