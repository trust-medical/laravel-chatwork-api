<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class UploadedRoomFile implements MapsFromArray
{
    public function __construct(
        public int $fileId,
    ) {}

    /**
     * @param  array{file_id?: int|numeric-string}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            fileId: (int) ($data['file_id'] ?? 0),
        );
    }
}
