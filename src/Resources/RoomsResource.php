<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedRoom;
use TrustMedical\LaravelChatworkApi\Data\Responses\NoContentData;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedRoom;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/rooms` エンドポイントグループ（一覧取得・単件取得・作成・更新・退室・削除）とネストリソースの公開 API。
 */
final class RoomsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザーが所属する全ルームを一覧取得する (GET /rooms)。
     *
     * @return list<RoomData> デフォルトの Dto モードでは配列を返す。他のレスポンスモードはそれぞれ対応する形式で返す
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function list(): mixed
    {
        return $this->client->sendList('GET', '/rooms', [], RoomData::class, 'listRooms');
    }

    /**
     * 新しいグループルームを作成する (POST /rooms)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function create(CreateRoomRequest $request): mixed
    {
        return $this->client->send(
            'POST',
            '/rooms',
            $request->toArray(),
            CreatedRoom::class,
            'createRoom',
        );
    }

    /**
     * 指定したルームの設定とメタデータを 1 件取得する (GET /rooms/{room_id})。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function find(int $roomId): mixed
    {
        return $this->client->send(
            'GET',
            sprintf('/rooms/%d', $roomId),
            [],
            RoomData::class,
            'getRoom',
        );
    }

    /**
     * 既存のルーム設定を更新する (PUT /rooms/{room_id})。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function update(int $roomId, UpdateRoomRequest $request): mixed
    {
        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d', $roomId),
            $request->toArray(),
            UpdatedRoom::class,
            'updateRoom',
        );
    }

    /**
     * 残りのメンバーにルームを残したまま退室する
     * (DELETE /rooms/{room_id} with action_type=leave)。
     *
     * ルームとその履歴は他の全員に対して保持され、認証ユーザーのみが除外される。
     * 全メンバーに対してルームを破壊する {@see self::deleteRoom()} と対比される。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function leaveRoom(int $roomId): mixed
    {
        return $this->leaveOrDelete($roomId, 'leave', 'leaveRoom');
    }

    /**
     * 全メンバーに対してルームを完全削除し、メッセージ・タスク・ファイルをすべて破壊する
     * (DELETE /rooms/{room_id} with action_type=delete)。
     *
     * 取り消し不能であり全メンバーに影響する。認証ユーザーのみを除外する
     * {@see self::leaveRoom()} と対比される。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function deleteRoom(int $roomId): mixed
    {
        return $this->leaveOrDelete($roomId, 'delete', 'deleteRoom');
    }

    private function leaveOrDelete(int $roomId, string $actionType, string $operationId): mixed
    {
        return $this->client->send(
            'DELETE',
            sprintf('/rooms/%d', $roomId),
            ['action_type' => $actionType],
            NoContentData::class,
            $operationId,
        );
    }

    public function messages(): RoomMessagesResource
    {
        return new RoomMessagesResource($this->client);
    }

    public function members(): RoomMembersResource
    {
        return new RoomMembersResource($this->client);
    }

    public function tasks(): RoomTasksResource
    {
        return new RoomTasksResource($this->client);
    }

    public function files(): RoomFilesResource
    {
        return new RoomFilesResource($this->client);
    }

    public function links(): RoomLinksResource
    {
        return new RoomLinksResource($this->client);
    }
}
