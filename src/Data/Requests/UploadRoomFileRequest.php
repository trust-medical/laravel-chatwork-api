<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class UploadRoomFileRequest
{
    private const int MESSAGE_MIN = 1;

    private const int MESSAGE_MAX = 65535;

    private const int FILE_MAX_BYTES = 5_242_880;

    public function __construct(
        public string $path,
        public ?string $filename = null,
        public ?string $message = null,
    ) {
        $this->validate();
    }

    public function filename(): string
    {
        return $this->filename ?? basename($this->path);
    }

    public function contents(): string
    {
        // validate() has already confirmed the path; read lazily so a large
        // file is not held in memory before the request is actually sent.
        return (string) file_get_contents($this->path);
    }

    /**
     * @return array<string, string>
     */
    public function toFields(): array
    {
        return $this->message !== null ? ['message' => $this->message] : [];
    }

    private function validate(): void
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new ChatworkValidationException(
                'file must be an existing readable path.',
                ['file' => ['must be an existing readable path']],
            );
        }

        $size = (int) filesize($this->path);
        if ($size <= 0) {
            throw new ChatworkValidationException(
                'file must not be empty.',
                ['file' => ['must not be empty']],
            );
        }
        if ($size > self::FILE_MAX_BYTES) {
            throw new ChatworkValidationException(
                sprintf('file must be %d bytes or less.', self::FILE_MAX_BYTES),
                ['file' => [sprintf('must be %d bytes or less', self::FILE_MAX_BYTES)]],
            );
        }

        if ($this->message !== null) {
            $length = mb_strlen($this->message);
            if ($length < self::MESSAGE_MIN) {
                throw new ChatworkValidationException(
                    'message must not be empty.',
                    ['message' => ['must not be empty']],
                );
            }
            if ($length > self::MESSAGE_MAX) {
                throw new ChatworkValidationException(
                    sprintf('message must be %d characters or less.', self::MESSAGE_MAX),
                    ['message' => [sprintf('must be %d characters or less', self::MESSAGE_MAX)]],
                );
            }
        }
    }
}
