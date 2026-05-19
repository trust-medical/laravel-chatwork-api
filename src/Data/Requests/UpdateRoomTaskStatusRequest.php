<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

/**
 * `PUT /rooms/{room_id}/tasks/{task_id}/status` のリクエストペイロード。
 *
 * Chatwork 仕様ではステータスを `body` フィールド（open / done）で送る。
 * 他の Resource と同じく payload 構築を Request DTO に委譲する。
 */
final readonly class UpdateRoomTaskStatusRequest
{
    public function __construct(
        public TaskStatus $status,
    ) {}

    /**
     * @return array{body: string}
     */
    public function toArray(): array
    {
        return ['body' => $this->status->value];
    }
}
