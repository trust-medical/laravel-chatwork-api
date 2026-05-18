<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

final readonly class RoomTaskData
{
    public function __construct(
        public int $taskId,
        public SimpleAccount $account,
        public SimpleAccount $assignedByAccount,
        public string $messageId,
        public string $body,
        public int $limitTime,
        public TaskStatus $status,
        public ?LimitType $limitType = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $account = $data['account'] ?? [];
        $assignedByAccount = $data['assigned_by_account'] ?? [];

        return new self(
            taskId: (int) ($data['task_id'] ?? 0),
            account: SimpleAccount::fromArray(is_array($account) ? $account : []),
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
