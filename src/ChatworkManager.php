<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Contracts\Container\Container;
use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\Credentials;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthTokenProvider;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

final class ChatworkManager
{
    private ?Connection $connection = null;

    private ResponseMode $mode = ResponseMode::Dto;

    private ?Credentials $credentialsOverride = null;

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
        $new = clone $this;
        $new->credentialsOverride = new ApiTokenCredentials($token);

        return $new;
    }

    public function withBearerToken(string $token): self
    {
        $new = clone $this;
        $new->credentialsOverride = new BearerTokenCredentials($token);

        return $new;
    }

    public function asArray(): self
    {
        return $this->withMode(ResponseMode::Array);
    }

    public function asDto(): self
    {
        return $this->withMode(ResponseMode::Dto);
    }

    public function asCollection(): self
    {
        return $this->withMode(ResponseMode::Collection);
    }

    public function asResponse(): self
    {
        return $this->withMode(ResponseMode::Response);
    }

    public function asPsrResponse(): self
    {
        return $this->withMode(ResponseMode::PsrResponse);
    }

    public function asResult(): self
    {
        return $this->withMode(ResponseMode::Result);
    }

    public function getConnection(): Connection
    {
        return $this->connection ?? $this->resolveConnection($this->defaultConnectionName());
    }

    public function getEffectiveConnection(): Connection
    {
        $base = $this->getConnection();

        if ($this->credentialsOverride === null) {
            return $base;
        }

        return Connection::make(
            name: $base->name,
            credentials: $this->credentialsOverride,
            baseUri: $base->baseUri,
            timeout: $base->timeout,
        );
    }

    public function getMode(): ResponseMode
    {
        return $this->mode;
    }

    public function client(): ChatworkClient
    {
        return new ChatworkClient(
            $this->getEffectiveConnection(),
            $this->container->make(ChatworkPendingRequestFactory::class),
            $this->container->make(ResponseMapper::class),
            $this->mode,
        );
    }

    public function rooms(): RoomsResource
    {
        return $this->client()->rooms();
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

    private function withMode(ResponseMode $mode): self
    {
        $new = clone $this;
        $new->mode = $mode;

        return $new;
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

        $auth = $entry['auth'] ?? null;
        $credentials = match ($auth) {
            'api_token' => $this->buildStaticCredentials($name, $entry, ApiTokenCredentials::class),
            'bearer' => $this->buildStaticCredentials($name, $entry, BearerTokenCredentials::class),
            'oauth' => $this->buildOAuthCredentials($name, $entry),
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

    /**
     * @param  array<string, mixed>  $entry
     * @param  class-string<ApiTokenCredentials|BearerTokenCredentials>  $credentialsClass
     */
    private function buildStaticCredentials(string $name, array $entry, string $credentialsClass): Credentials
    {
        $token = $entry['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new ChatworkAuthenticationException(
                sprintf("Chatwork connection '%s' has no token configured.", $name),
            );
        }

        return new $credentialsClass($token);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function buildOAuthCredentials(string $name, array $entry): Credentials
    {
        $config = $this->container->make('config');

        $tokenSetKey = $entry['connection_name'] ?? $name;
        if (! is_string($tokenSetKey) || $tokenSetKey === '') {
            $tokenSetKey = $name;
        }

        $leeway = $config->get('chatwork.oauth.refresh_leeway_seconds');

        $provider = new OAuthTokenProvider(
            connectionName: $tokenSetKey,
            repository: $this->container->make(TokenRepository::class),
            oauth: $this->container->make(OAuthClient::class),
            leewaySeconds: is_numeric($leeway) ? (int) $leeway : 60,
        );

        return $provider->credentials();
    }
}
