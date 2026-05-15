<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class RoomMessagesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function create(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (client=%s, roomId=%d, body_length=%d, selfUnread=%s)',
            $this->client::class,
            $roomId,
            strlen($body),
            $selfUnread === null ? 'null' : ($selfUnread ? 'true' : 'false'),
        ));
    }

    public function list(int $roomId, ?bool $force = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, force=%s)',
            $roomId,
            $force === null ? 'null' : ($force ? 'true' : 'false'),
        ));
    }

    public function find(int $roomId, string $messageId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, messageId=%s)', $roomId, $messageId));
    }

    public function update(int $roomId, string $messageId, string $body): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, messageId=%s, body_length=%d)',
            $roomId,
            $messageId,
            strlen($body),
        ));
    }

    public function deleteMessage(int $roomId, string $messageId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, messageId=%s)', $roomId, $messageId));
    }

    public function markAsRead(int $roomId, ?string $messageId = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, messageId=%s)',
            $roomId,
            $messageId ?? 'null',
        ));
    }

    public function markAsUnread(int $roomId, string $messageId): mixed
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%d, messageId=%s)', $roomId, $messageId));
    }
}
