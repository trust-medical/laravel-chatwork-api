<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use TrustMedical\LaravelChatworkApi\Auth\Credentials;

final readonly class Connection
{
    public function __construct(
        public string $name,
        public Credentials $credentials,
        public string $baseUri = 'https://api.chatwork.com/v2',
        public int $timeout = 10,
    ) {}

    public static function make(
        string $name,
        Credentials $credentials,
        ?string $baseUri = null,
        ?int $timeout = null,
    ): self {
        return new self(
            name: $name,
            credentials: $credentials,
            baseUri: $baseUri ?? 'https://api.chatwork.com/v2',
            timeout: $timeout ?? 10,
        );
    }
}
