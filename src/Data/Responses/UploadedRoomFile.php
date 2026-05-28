<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class UploadedRoomFile implements MapsFromArray
{
    public function __construct(
        public int $fileId,
        public string $messageId = '',
        public string $filename = '',
        public int $filesize = 0,
        public int $uploadTime = 0,
        public ?SimpleAccount $account = null,
    ) {}

    /**
     * @param  array{
     *     file_id?: int|numeric-string,
     *     message_id?: int|string,
     *     filename?: string,
     *     filesize?: int|numeric-string,
     *     upload_time?: int|numeric-string,
     *     account?: mixed
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        $account = $data['account'] ?? null;

        return new self(
            fileId: (int) ($data['file_id'] ?? 0),
            messageId: (string) ($data['message_id'] ?? ''),
            filename: (string) ($data['filename'] ?? ''),
            filesize: (int) ($data['filesize'] ?? 0),
            uploadTime: (int) ($data['upload_time'] ?? 0),
            account: is_array($account) ? SimpleAccount::fromArray($account) : null,
        );
    }
}
