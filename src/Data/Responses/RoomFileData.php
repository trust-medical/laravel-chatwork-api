<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class RoomFileData
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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
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
