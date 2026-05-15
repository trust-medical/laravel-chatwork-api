<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Connection;

final class ChatworkRoute
{
    private function __construct(
        private readonly int|string $roomId,
        private ?string $connectionName = null,
        private ?Connection $connection = null,
    ) {}

    public static function room(int|string $roomId): self
    {
        return new self($roomId);
    }

    public function connection(string $name): self
    {
        return new self($this->roomId, connectionName: $name, connection: null);
    }

    public function using(Connection $connection): self
    {
        return new self($this->roomId, connectionName: null, connection: $connection);
    }

    public function roomId(): int|string
    {
        return $this->roomId;
    }

    public function connectionName(): ?string
    {
        return $this->connectionName;
    }

    public function getConnection(): ?Connection
    {
        return $this->connection;
    }
}
