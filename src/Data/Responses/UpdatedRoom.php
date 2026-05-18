<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class UpdatedRoom
{
    public function __construct(public int $roomId) {}

    /**
     * @param  array{room_id?: int|numeric-string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((int) ($data['room_id'] ?? 0));
    }
}
