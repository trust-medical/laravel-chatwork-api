<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class RoomMessagesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function create(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        $request = new CreateMessageRequest($body, $selfUnread);

        return $this->client->send(
            'POST',
            sprintf('/rooms/%d/messages', $roomId),
            $request->toArray(),
            CreatedMessage::class,
            'createRoomMessage',
        );
    }

    public function list(int $roomId, ?bool $force = null): mixed
    {
        $query = $force === true ? ['force' => 1] : [];
        $path = sprintf('/rooms/%d/messages', $roomId);

        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                $query,
                MessageData::class,
                'listRoomMessages',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, $query, MessageData::class, 'listRoomMessages');
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
