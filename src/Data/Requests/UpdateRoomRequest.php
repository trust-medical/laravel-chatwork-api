<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * `PUT /rooms/{room_id}` の partial update リクエスト。
 *
 * 全フィールドは任意。フィールドを 1 つも指定しないインスタンスは空ペイロード `[]` を返す。
 * 空更新に対しては Chatwork API が HTTP 400 を返し、`ChatworkRequestException`
 * （result モードでは `Result`）として表面化する。「最低 1 フィールド必須」は
 * サーバ側の責務であり、本パッケージは送信前バリデーションを行わない（`architecture.md` 方針）。
 */
final readonly class UpdateRoomRequest
{
    private const int NAME_MIN = 1;

    private const int NAME_MAX = 255;

    /**
     * @throws ChatworkValidationException null でない name が空、または文字数上限を超えた場合。
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?IconPreset $iconPreset = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{name?: string, description?: string, icon_preset?: string}
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
