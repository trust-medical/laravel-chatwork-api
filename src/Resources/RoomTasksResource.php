<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomTaskStatusRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedTask;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/rooms/{room_id}/tasks` エンドポイントグループ（一覧取得・単件取得・作成・ステータス更新）の公開 API。
 *
 * @api 公開 API。宣言戻り値型は asDto() モード契約（他モードは実行時型が変わる。型対応表は README / docs を参照）。
 */
final class RoomTasksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * ルームのタスク一覧を取得する。担当者・作成者・ステータスで絞り込み可能
     * (GET /rooms/{room_id}/tasks)。
     *
     * @return list<RoomTaskData> asDto() 契約。他モードは ResponseMode に従い実行時型が変わる
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function list(
        int $roomId,
        ?int $accountId = null,
        ?int $assignedByAccountId = null,
        ?TaskStatus $status = null,
    ): mixed {
        $query = [];
        if ($accountId !== null) {
            $query['account_id'] = $accountId;
        }
        if ($assignedByAccountId !== null) {
            $query['assigned_by_account_id'] = $assignedByAccountId;
        }
        if ($status !== null) {
            $query['status'] = $status->value;
        }

        return $this->client->sendList(
            'GET',
            sprintf('/rooms/%d/tasks', $roomId),
            $query,
            RoomTaskData::class,
            'listRoomTasks',
        );
    }

    /**
     * ルームに 1 人以上のメンバーを担当者として割り当てたタスクを作成する
     * (POST /rooms/{room_id}/tasks)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function create(int $roomId, CreateRoomTaskRequest $request): mixed
    {
        return $this->client->send(
            'POST',
            sprintf('/rooms/%d/tasks', $roomId),
            $request->toArray(),
            CreatedTask::class,
            'createRoomTask',
        );
    }

    /**
     * 指定した ID のタスクを 1 件取得する (GET /rooms/{room_id}/tasks/{task_id})。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function find(int $roomId, int $taskId): mixed
    {
        return $this->client->send(
            'GET',
            sprintf('/rooms/%d/tasks/%d', $roomId, $taskId),
            [],
            RoomTaskData::class,
            'getRoomTask',
        );
    }

    /**
     * タスクを未完了または完了に更新する (PUT /rooms/{room_id}/tasks/{task_id}/status)。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function updateStatus(int $roomId, int $taskId, TaskStatus $status): mixed
    {
        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/tasks/%d/status', $roomId, $taskId),
            (new UpdateRoomTaskStatusRequest($status))->toArray(),
            RoomTaskData::class,
            'updateRoomTaskStatus',
        );
    }
}
