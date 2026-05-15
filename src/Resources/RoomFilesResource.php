<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;

final class RoomFilesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(int $roomId, ?int $accountId = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (client=%s, roomId=%d, accountId=%s)',
            $this->client::class,
            $roomId,
            $accountId === null ? 'null' : (string) $accountId,
        ));
    }

    public function upload(int $roomId, mixed $file, ?string $message = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, file=%s, message_length=%d)',
            $roomId,
            is_object($file) ? $file::class : gettype($file),
            strlen($message ?? ''),
        ));
    }

    public function find(int $roomId, int $fileId, ?bool $createDownloadUrl = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (roomId=%d, fileId=%d, dl=%s)',
            $roomId,
            $fileId,
            $createDownloadUrl === null ? 'null' : ($createDownloadUrl ? 'true' : 'false'),
        ));
    }
}
