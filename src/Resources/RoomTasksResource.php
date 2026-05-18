<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedTask;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

final class RoomTasksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

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

        $path = sprintf('/rooms/%d/tasks', $roomId);

        // ResponseMode::Dto is the package default but the wire shape here is an
        // array of tasks, so internally route through Collection mode and
        // unwrap. Other modes (Collection / Array / Response / PsrResponse /
        // Result) flow straight through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                $query,
                RoomTaskData::class,
                'listRoomTasks',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, $query, RoomTaskData::class, 'listRoomTasks');
    }

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

    public function updateStatus(int $roomId, int $taskId, TaskStatus $status): mixed
    {
        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/tasks/%d/status', $roomId, $taskId),
            ['body' => $status->value],
            RoomTaskData::class,
            'updateRoomTaskStatus',
        );
    }
}
