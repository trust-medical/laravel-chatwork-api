<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\ReplacedRoomMembers;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Chatwork `/rooms/{room_id}/members` エンドポイントグループ（メンバー一覧取得・一括置換）の公開 API。
 */
final class RoomMembersResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * ルームの全メンバーをロール情報付きで一覧取得する (GET /rooms/{room_id}/members)。
     *
     * @return list<RoomMemberData> デフォルトの Dto モードでは配列を返す。他のレスポンスモードはそれぞれ対応する形式で返す
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function list(int $roomId): mixed
    {
        $path = sprintf('/rooms/%d/members', $roomId);

        // ResponseMode::Dto はパッケージのデフォルトだが、ワイヤー形式はメンバーの配列なので、
        // 内部的に Collection モード経由で送信してアンラップする。
        // 他のモード (Collection / Array / Response / PsrResponse / Result) は
        // ChatworkClient::send をそのまま通過する。
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                [],
                RoomMemberData::class,
                'listRoomMembers',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, [], RoomMemberData::class, 'listRoomMembers');
    }

    /**
     * ルームのメンバーシップとロール割り当てを一括で完全置換する
     * (PUT /rooms/{room_id}/members)。
     *
     * これは差分追加ではなく完全置換であり、リクエストのリストに含まれないアカウントは
     * ルームから除外される。リクエストオブジェクトは送信前にリストを検証する
     * （管理者 ID が 1 件以上必要、全 ID が正の整数）ため、不正な入力は
     * レスポンスモードに関わらず即失敗する。
     *
     * @throws ChatworkValidationException members_admin_ids が空、またはいずれかの ID リストに正でない整数が含まれる場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function replaceMembers(int $roomId, ReplaceRoomMembersRequest $request): mixed
    {
        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/members', $roomId),
            $request->toArray(),
            ReplacedRoomMembers::class,
            'replaceRoomMembers',
        );
    }
}
