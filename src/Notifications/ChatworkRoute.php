<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Connection;

/**
 * Chatwork チャンネルのイミュータブルなルーティング対象。
 *
 * ルーム ID とオプションの connection セレクターを保持する。connection()
 * （名前指定）と using()（明示 Connection）は排他: いずれも相手セレクターを
 * リセットした新規インスタンスを返す。
 */
final class ChatworkRoute
{
    private function __construct(
        private readonly int|string $roomId,
        private readonly ?string $connectionName = null,
        private readonly ?Connection $connection = null,
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
