<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class RoomLinksResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function find(int $roomId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (client=%s, roomId=%d)', $this->client::class, $roomId));
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function create(int $roomId, array $request): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, keys=%s)', $roomId, implode(',', array_keys($request))));
    }

    /**
     * @param  array<string, mixed>  $request
     */
    public function update(int $roomId, array $request): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, keys=%s)', $roomId, implode(',', array_keys($request))));
    }

    public function deleteLink(int $roomId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d)', $roomId));
    }
}
