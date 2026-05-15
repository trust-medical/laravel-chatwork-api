<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Contracts\Container\Container;
use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

final class ChatworkManager
{
    private ?Connection $connection = null;

    private ResponseMode $mode = ResponseMode::Dto;

    public function __construct(private readonly Container $container) {}

    public function connection(?string $name = null): self
    {
        $resolved = $name ?? $this->defaultConnectionName();

        $new = clone $this;
        $new->connection = $this->resolveConnection($resolved);

        return $new;
    }

    public function forConnection(Connection $connection): self
    {
        $new = clone $this;
        $new->connection = $connection;

        return $new;
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

    public function getConnection(): Connection
    {
        return $this->connection ?? $this->resolveConnection($this->defaultConnectionName());
    }

    public function getEffectiveConnection(): Connection
    {
        return $this->getConnection();
    }

    public function getMode(): ResponseMode
    {
        return $this->mode;
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

    private function resolveConnection(string $name): Connection
    {
        $config = $this->container->make('config');
        $entry = $config->get(sprintf('chatwork.connections.%s', $name));

        if (! is_array($entry)) {
            throw new ChatworkAuthenticationException(
                sprintf("Chatwork connection '%s' is not configured.", $name),
            );
        }

        $token = $entry['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new ChatworkAuthenticationException(
                sprintf("Chatwork connection '%s' has no token configured.", $name),
            );
        }

        $auth = $entry['auth'] ?? null;
        $credentials = match ($auth) {
            'api_token' => new ApiTokenCredentials($token),
            'bearer' => new BearerTokenCredentials($token),
            default => throw new ChatworkAuthenticationException(
                sprintf(
                    "Chatwork connection '%s' has unsupported auth driver: %s",
                    $name,
                    is_string($auth) ? $auth : 'null',
                ),
            ),
        };

        $baseUri = $config->get('chatwork.base_uri');
        $timeout = $config->get(sprintf('chatwork.connections.%s.timeout', $name))
            ?? $config->get('chatwork.timeout');

        return Connection::make(
            name: $name,
            credentials: $credentials,
            baseUri: is_string($baseUri) ? $baseUri : null,
            timeout: is_numeric($timeout) ? (int) $timeout : null,
        );
    }

    private function defaultConnectionName(): string
    {
        $name = $this->container->make('config')->get('chatwork.default');

        return is_string($name) && $name !== '' ? $name : 'default';
    }
}
