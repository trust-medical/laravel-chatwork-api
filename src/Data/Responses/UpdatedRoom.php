<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class UpdatedRoom implements MapsFromArray
{
    public function __construct(public int $roomId) {}

    /**
     * @param  array{room_id?: int|numeric-string}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self((int) ($data['room_id'] ?? 0));
    }
}
