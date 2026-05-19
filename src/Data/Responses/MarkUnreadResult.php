<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

/**
 * `PUT /rooms/{room_id}/messages/unread` の結果。
 *
 * MarkReadResult と構造は同一だが、呼び出し側が戻り値の意味を型で
 * 区別できるよう意図的に別クラスに保つ（統合しない）。
 */
final readonly class MarkUnreadResult implements MapsFromArray
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
