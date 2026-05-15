<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Connection;

final class ChatworkRoute
{
    public static function room(int|string $roomId): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%s)', (string) $roomId));
    }

    public function connection(string $name): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (name=%s)', $name));
    }

    public function using(Connection $connection): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (connection=%s)', $connection->name));
    }

    public function roomId(): int|string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function connectionName(): ?string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function getConnection(): ?Connection
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
