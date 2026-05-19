<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Data\Responses\IncomingRequestData;
use TrustMedical\LaravelChatworkApi\Data\Responses\NoContentData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/incoming_requests` エンドポイントグループ（list / accept / decline）の公開 API。
 */
final class IncomingRequestsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザー宛の未承認コンタクト申請一覧を取得する (GET /incoming_requests)。
     *
     * @return list<IncomingRequestData> デフォルト Dto モード時（204 は `[]` に縮退）；他の ResponseMode はそれぞれの型を返す
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function list(): mixed
    {
        return $this->client->sendList(
            'GET',
            '/incoming_requests',
            [],
            IncomingRequestData::class,
            'listIncomingRequests',
        );
    }

    /**
     * コンタクト申請を承認し、申請者をコンタクトに変換する
     * (PUT /incoming_requests/{request_id})。
     *
     * 200 レスポンスボディは承認済みアカウント情報で、コンタクトと
     * バイト単位で同一のため、DTO として {@see ContactData} を再利用する。
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function accept(int $requestId): mixed
    {
        // 仕様上リクエストボディが存在しないため、ペイロードは空。
        // 200 レスポンスはコンタクト化されたアカウント情報で、ContactData と
        // バイト単位で同一のため、ここで ContactData を再利用する。
        return $this->client->send(
            'PUT',
            sprintf('/incoming_requests/%d', $requestId),
            [],
            ContactData::class,
            'acceptIncomingRequest',
        );
    }

    /**
     * コンタクト申請を拒否する (DELETE /incoming_requests/{request_id})。
     *
     * エンドポイントは 204 No Content を返し、{@see NoContentData} にマップされる。
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function decline(int $requestId): mixed
    {
        // 仕様上 204 No Content でボディなし。NoContentData にマップする。
        return $this->client->send(
            'DELETE',
            sprintf('/incoming_requests/%d', $requestId),
            [],
            NoContentData::class,
            'declineIncomingRequest',
        );
    }
}
