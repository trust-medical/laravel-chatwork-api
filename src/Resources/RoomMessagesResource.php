<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\MarkAsUnreadRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateMessageRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Data\Responses\DeletedMessage;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkReadResult;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkUnreadResult;
use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedMessage;
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

        // ResponseMode::Dto is the package default but the wire shape here is an
        // array of messages, so internally route through Collection mode and
        // unwrap. Other modes (Collection / Array / Response / PsrResponse /
        // Result) flow straight through ChatworkClient::send unchanged.
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
        return $this->client->send(
            'GET',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            [],
            MessageData::class,
            'getRoomMessage',
        );
    }

    public function update(int $roomId, string $messageId, string $body): mixed
    {
        $request = new UpdateMessageRequest($body);

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            $request->toArray(),
            UpdatedMessage::class,
            'updateRoomMessage',
        );
    }

    public function deleteMessage(int $roomId, string $messageId): mixed
    {
        return $this->client->send(
            'DELETE',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            [],
            DeletedMessage::class,
            'deleteRoomMessage',
        );
    }

    public function markAsRead(int $roomId, ?string $messageId = null): mixed
    {
        // Chatwork treats an absent message_id as "mark everything up to now as
        // read", so null and empty string both mean "omit the field" here. This
        // intentionally differs from markAsUnread, where message_id is required
        // and an empty string is rejected before sending.
        $payload = $messageId !== null && $messageId !== ''
            ? ['message_id' => $messageId]
            : [];

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/read', $roomId),
            $payload,
            MarkReadResult::class,
            'markRoomMessagesAsRead',
        );
    }

    public function markAsUnread(int $roomId, string $messageId): mixed
    {
        $request = new MarkAsUnreadRequest($messageId);

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/unread', $roomId),
            $request->toArray(),
            MarkUnreadResult::class,
            'markRoomMessagesAsUnread',
        );
    }
}
