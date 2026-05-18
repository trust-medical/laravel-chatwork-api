<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class MarkReadResult implements MapsFromArray
{
    public function __construct(
        public int $unreadNum,
        public int $mentionNum,
    ) {}

    /**
     * @param  array{unread_num?: int|string, mention_num?: int|string}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            unreadNum: (int) ($data['unread_num'] ?? 0),
            mentionNum: (int) ($data['mention_num'] ?? 0),
        );
    }
}
