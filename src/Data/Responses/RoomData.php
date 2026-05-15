<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class RoomData
{
    public function __construct(
        public int $roomId,
        public string $name,
        public string $type,
        public string $role,
        public bool $sticky,
        public int $unreadNum,
        public int $mentionNum,
        public int $myTaskNum,
        public int $messageNum,
        public int $fileNum,
        public int $taskNum,
        public string $iconPath,
        public int $lastUpdateTime,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roomId: (int) ($data['room_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            role: (string) ($data['role'] ?? ''),
            sticky: (bool) ($data['sticky'] ?? false),
            unreadNum: (int) ($data['unread_num'] ?? 0),
            mentionNum: (int) ($data['mention_num'] ?? 0),
            myTaskNum: (int) ($data['mytask_num'] ?? 0),
            messageNum: (int) ($data['message_num'] ?? 0),
            fileNum: (int) ($data['file_num'] ?? 0),
            taskNum: (int) ($data['task_num'] ?? 0),
            iconPath: (string) ($data['icon_path'] ?? ''),
            lastUpdateTime: (int) ($data['last_update_time'] ?? 0),
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}
