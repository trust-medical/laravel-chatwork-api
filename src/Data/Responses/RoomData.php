<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class RoomData implements MapsFromArray
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
     * @param  array{
     *     room_id?: int|numeric-string,
     *     name?: string,
     *     type?: string,
     *     role?: string,
     *     sticky?: bool,
     *     unread_num?: int|numeric-string,
     *     mention_num?: int|numeric-string,
     *     mytask_num?: int|numeric-string,
     *     message_num?: int|numeric-string,
     *     file_num?: int|numeric-string,
     *     task_num?: int|numeric-string,
     *     icon_path?: string,
     *     last_update_time?: int|numeric-string,
     *     description?: string
     * }  $data
     */
    public static function fromArray(array $data): static
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
