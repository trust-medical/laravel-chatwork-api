<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Contracts\Container\Container;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

final class ChatworkManager
{
    public function __construct(private readonly Container $container) {}

    public function connection(?string $name = null): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (container=%s, name=%s)', $this->container::class, $name ?? 'default'));
    }

    public function forConnection(Connection $connection): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (connection=%s)', $connection->name));
    }

    public function withApiToken(string $token): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (token_length=%d)', strlen($token)));
    }

    public function withBearerToken(string $token): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (token_length=%d)', strlen($token)));
    }

    public function asArray(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function asDto(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function asCollection(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function asResponse(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function asPsrResponse(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function asResult(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function client(): ChatworkClient
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function rooms(): RoomsResource
    {
        throw new \LogicException('not implemented in Phase 0');
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
}
