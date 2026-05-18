<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class MyStatusData
{
    public function __construct(
        public int $unreadRoomNum,
        public int $mentionRoomNum,
        public int $mytaskRoomNum,
        public int $unreadNum,
        public int $mentionNum,
        public int $mytaskNum,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            unreadRoomNum: (int) ($data['unread_room_num'] ?? 0),
            mentionRoomNum: (int) ($data['mention_room_num'] ?? 0),
            mytaskRoomNum: (int) ($data['mytask_room_num'] ?? 0),
            unreadNum: (int) ($data['unread_num'] ?? 0),
            mentionNum: (int) ($data['mention_num'] ?? 0),
            mytaskNum: (int) ($data['mytask_num'] ?? 0),
        );
    }
}
