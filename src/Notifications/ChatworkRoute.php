<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;

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

    /**
     * ルーム ID を検証してルートを生成する（唯一の生成口）。
     *
     * すべての ChatworkRoute はこのファクトリを経由するため、ここでの検証で
     * 非数値ルーム ID が `(int)` キャストで 0 に黙殺され誤ルーム送信される
     * 事故を防ぐ。int は正、string は正の整数文字列（空・非数字・先頭ゼロ不可）。
     *
     * @throws ChatworkRoutingException ルーム ID が正の整数でない場合。
     */
    public static function room(int|string $roomId): self
    {
        $valid = is_int($roomId)
            ? $roomId > 0
            : ($roomId !== '' && ctype_digit($roomId) && $roomId[0] !== '0');

        if (! $valid) {
            throw new ChatworkRoutingException(
                sprintf('Chatwork route room id must be a positive integer, got "%s".', (string) $roomId),
                ['route' => ['invalid room id']],
            );
        }

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
