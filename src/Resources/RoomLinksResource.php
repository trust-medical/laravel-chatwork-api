<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomLinkData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/rooms/{room_id}/link` エンドポイントグループ（招待リンクの取得・作成・更新・削除）の公開 API。
 */
final class RoomLinksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * ルームの現在の招待リンク設定を取得する (GET /rooms/{room_id}/link)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function find(int $roomId): mixed
    {
        return $this->client->send(
            'GET',
            sprintf('/rooms/%d/link', $roomId),
            [],
            RoomLinkData::class,
            'getRoomLink',
        );
    }

    /**
     * ルームの招待リンクを有効化して設定する (POST /rooms/{room_id}/link)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function create(int $roomId, RoomLinkRequest $request): mixed
    {
        return $this->client->send(
            'POST',
            sprintf('/rooms/%d/link', $roomId),
            $request->toArray(),
            RoomLinkData::class,
            'createRoomLink',
        );
    }

    /**
     * 既存の招待リンクの設定を更新する (PUT /rooms/{room_id}/link)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function update(int $roomId, RoomLinkRequest $request): mixed
    {
        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/link', $roomId),
            $request->toArray(),
            RoomLinkData::class,
            'updateRoomLink',
        );
    }

    /**
     * ルームの招待リンクを無効化する (DELETE /rooms/{room_id}/link)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function deleteLink(int $roomId): mixed
    {
        return $this->client->send(
            'DELETE',
            sprintf('/rooms/%d/link', $roomId),
            [],
            RoomLinkData::class,
            'deleteRoomLink',
        );
    }
}
