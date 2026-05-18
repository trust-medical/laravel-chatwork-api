<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class SimpleRoom
{
    public function __construct(
        public int $roomId,
        public string $name,
        public string $iconPath,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roomId: (int) ($data['room_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            iconPath: (string) ($data['icon_path'] ?? ''),
        );
    }
}
