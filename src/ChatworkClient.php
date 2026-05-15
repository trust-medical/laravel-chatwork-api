<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use InvalidArgumentException;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
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
        private readonly ResponseMode $mode = ResponseMode::Dto,
    ) {}

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function mode(): ResponseMode
    {
        return $this->mode;
    }

    public function withMode(ResponseMode $mode): self
    {
        return new self($this->connection, $this->factory, $this->mapper, $mode);
    }

    public function rooms(): RoomsResource
    {
        return new RoomsResource($this);
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
    public function send(
        string $method,
        string $path,
        array $payload = [],
        ?string $dtoClass = null,
        ?string $operationId = null,
    ): mixed {
        $verb = strtoupper($method);
        $pending = $this->factory->create($this->connection);

        $response = match ($verb) {
            'POST' => $pending->asForm()->post($path, $payload),
            'PUT' => $pending->asForm()->put($path, $payload),
            'GET' => $pending->get($path, $payload),
            'DELETE' => $pending->delete($path, $payload),
            default => throw new InvalidArgumentException(sprintf('Unsupported HTTP method: %s', $verb)),
        };

        return $this->mapper->map($response, $this->mode, $dtoClass, $verb, $path, $operationId);
    }

    public function createRoomMessage(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        return $this->rooms()->messages()->create($roomId, $body, $selfUnread);
    }
}
