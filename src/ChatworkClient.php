<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

final class ChatworkClient
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ChatworkPendingRequestFactory $factory,
        private readonly ResponseMapper $mapper,
    ) {}

    public function connection(): Connection
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (name=%s)', $this->connection->name));
    }

    public function rooms(): RoomsResource
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (factory=%s, mapper=%s)', $this->factory::class, $this->mapper::class));
    }

    public function me(): MeResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function my(): MyResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function contacts(): ContactsResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function incomingRequests(): IncomingRequestsResource
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  class-string|null  $dtoClass
     */
    public function send(string $method, string $path, array $payload = [], ?string $dtoClass = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (method=%s, path=%s, payload_keys=%s, dto=%s)',
            $method,
            $path,
            implode(',', array_keys($payload)),
            $dtoClass ?? 'null',
        ));
    }

    public function createRoomMessage(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, body_length=%d, selfUnread=%s)',
            $roomId,
            strlen($body),
            $selfUnread === null ? 'null' : ($selfUnread ? 'true' : 'false'),
        ));
    }
}
