<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomLinkData;

final class RoomLinksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

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
