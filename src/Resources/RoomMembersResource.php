<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\ReplacedRoomMembers;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class RoomMembersResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(int $roomId): mixed
    {
        $path = sprintf('/rooms/%d/members', $roomId);

        // ResponseMode::Dto is the package default but the wire shape here is an
        // array of members, so internally route through Collection mode and
        // unwrap. Other modes (Collection / Array / Response / PsrResponse /
        // Result) flow straight through ChatworkClient::send unchanged.
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
