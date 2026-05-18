<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class RoomFileData implements MapsFromArray
{
    public function __construct(
        public int $fileId,
        public SimpleAccount $account,
        public string $messageId,
        public string $filename,
        public int $filesize,
        public int $uploadTime,
        public ?string $downloadUrl = null,
    ) {}

    /**
     * @param  array{
     *     file_id?: int|numeric-string,
     *     account?: mixed,
     *     message_id?: int|string,
     *     filename?: string,
     *     filesize?: int|numeric-string,
     *     upload_time?: int|numeric-string,
     *     download_url?: string
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        $account = $data['account'] ?? [];

        return new self(
            fileId: (int) ($data['file_id'] ?? 0),
            account: SimpleAccount::fromArray(is_array($account) ? $account : []),
            messageId: (string) ($data['message_id'] ?? ''),
            filename: (string) ($data['filename'] ?? ''),
            filesize: (int) ($data['filesize'] ?? 0),
            uploadTime: (int) ($data['upload_time'] ?? 0),
            downloadUrl: isset($data['download_url']) ? (string) $data['download_url'] : null,
        );
    }
}
