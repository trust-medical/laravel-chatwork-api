<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

final readonly class MyTaskData implements MapsFromArray
{
    public function __construct(
        public int $taskId,
        public SimpleRoom $room,
        public SimpleAccount $assignedByAccount,
        public string $messageId,
        public string $body,
        public int $limitTime,
        public TaskStatus $status,
        public ?LimitType $limitType = null,
    ) {}

    /**
     * @param  array{
     *     task_id?: int|string,
     *     room?: mixed,
     *     assigned_by_account?: mixed,
     *     message_id?: string|int,
     *     body?: string,
     *     limit_time?: int|string,
     *     status?: string,
     *     limit_type?: string
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        $room = $data['room'] ?? [];
        $assignedByAccount = $data['assigned_by_account'] ?? [];

        return new self(
            taskId: (int) ($data['task_id'] ?? 0),
            room: SimpleRoom::fromArray(is_array($room) ? $room : []),
            assignedByAccount: SimpleAccount::fromArray(is_array($assignedByAccount) ? $assignedByAccount : []),
            messageId: (string) ($data['message_id'] ?? ''),
            body: (string) ($data['body'] ?? ''),
            limitTime: (int) ($data['limit_time'] ?? 0),
            // fromArray は throw しない方針。未知 status は未完了側に倒す。
            status: TaskStatus::tryFrom((string) ($data['status'] ?? '')) ?? TaskStatus::Open,
            limitType: isset($data['limit_type'])
                ? LimitType::tryFrom((string) $data['limit_type'])
                : null,
        );
    }
}
