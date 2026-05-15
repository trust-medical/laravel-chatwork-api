<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class UpdateRoomRequest
{
    private const int NAME_MIN = 1;

    private const int NAME_MAX = 255;

    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?IconPreset $iconPreset = null,
    ) {
        $this->validate();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->name !== null) {
            $payload['name'] = $this->name;
        }
        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }
        if ($this->iconPreset !== null) {
            $payload['icon_preset'] = $this->iconPreset->value;
        }

        return $payload;
    }

    private function validate(): void
    {
        if ($this->name === null) {
            return;
        }

        $length = mb_strlen($this->name);
        if ($length < self::NAME_MIN) {
            throw new ChatworkValidationException(
                'Room name must not be empty.',
                ['name' => ['must not be empty']],
            );
        }
        if ($length > self::NAME_MAX) {
            throw new ChatworkValidationException(
                sprintf('Room name must be %d characters or less.', self::NAME_MAX),
                ['name' => [sprintf('must be %d characters or less', self::NAME_MAX)]],
            );
        }
    }
}
