<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class UploadedRoomFile
{
    public function __construct(
        public int $fileId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileId: (int) ($data['file_id'] ?? 0),
        );
    }
}
