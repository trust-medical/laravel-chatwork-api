<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class SimpleRoom implements MapsFromArray
{
    public function __construct(
        public int $roomId,
        public string $name,
        public string $iconPath,
    ) {}

    /**
     * @param  array{
     *     room_id?: int|numeric-string,
     *     name?: string,
     *     icon_path?: string
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            roomId: (int) ($data['room_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            iconPath: (string) ($data['icon_path'] ?? ''),
        );
    }
}
