<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class RoomTasksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(int $roomId, array $filters = []): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s, roomId=%d, filters=%s)', $this->client::class, $roomId, implode(',', array_keys($filters))));
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function create(int $roomId, array $request): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, keys=%s)', $roomId, implode(',', array_keys($request))));
    }

    public function find(int $roomId, int $taskId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, taskId=%d)', $roomId, $taskId));
    }

    public function updateStatus(int $roomId, int $taskId, string $status): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, taskId=%d, status=%s)', $roomId, $taskId, $status));
    }
}
