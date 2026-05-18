<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class RoomLinkRequest
{
    private const int CODE_MIN = 1;

    private const int CODE_MAX = 50;

    public function __construct(
        public ?string $code = null,
        public ?bool $needAcceptance = null,
        public ?string $description = null,
    ) {
        $this->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->code !== null) {
            $payload['code'] = $this->code;
        }
        if ($this->needAcceptance !== null) {
            $payload['need_acceptance'] = $this->needAcceptance ? 1 : 0;
        }
        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }

        return $payload;
    }

    private function validate(): void
    {
        if ($this->code === null) {
            return;
        }

        $length = mb_strlen($this->code);
        if ($length < self::CODE_MIN) {
            throw new ChatworkValidationException(
                'code must not be empty.',
                ['code' => ['must not be empty']],
            );
        }
        if ($length > self::CODE_MAX) {
            throw new ChatworkValidationException(
                sprintf('code must be %d characters or less.', self::CODE_MAX),
                ['code' => [sprintf('must be %d characters or less', self::CODE_MAX)]],
            );
        }
    }
}
