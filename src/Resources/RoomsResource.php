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
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class RoomsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        $path = '/rooms';

        // Dto mode unwraps the Collection so callers get array<RoomData>; other
        // modes (Collection / Array / Response / PsrResponse / Result) flow
        // through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                [],
                RoomData::class,
                'listRooms',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, [], RoomData::class, 'listRooms');
    }

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

    public function leaveRoom(int $roomId): mixed
    {
        return $this->leaveOrDelete($roomId, 'leave');
    }

    public function deleteRoom(int $roomId): mixed
    {
        return $this->leaveOrDelete($roomId, 'delete');
    }

    private function leaveOrDelete(int $roomId, string $actionType): mixed
    {
        return $this->client->send(
            'DELETE',
            sprintf('/rooms/%d', $roomId),
            ['action_type' => $actionType],
            NoContentData::class,
            'leaveOrDeleteRoom',
        );
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
