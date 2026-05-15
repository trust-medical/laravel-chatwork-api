<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class RoomsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s)', $this->client::class));
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function create(array $request): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (keys=%s)', implode(',', array_keys($request))));
    }

    public function find(int $roomId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d)', $roomId));
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function update(int $roomId, array $request): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, keys=%s)', $roomId, implode(',', array_keys($request))));
    }

    public function leaveRoom(int $roomId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d)', $roomId));
    }

    public function deleteRoom(int $roomId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d)', $roomId));
    }

    public function messages(): RoomMessagesResource
    {
        return new RoomMessagesResource($this->client);
    }

    public function members(): RoomMembersResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function tasks(): RoomTasksResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function files(): RoomFilesResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function links(): RoomLinksResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
