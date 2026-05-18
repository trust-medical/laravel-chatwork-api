<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\NormalizesIntegerList;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class CreateRoomTaskRequest
{
    use NormalizesIntegerList;

    private const int BODY_MIN = 1;

    private const int BODY_MAX = 65535;

    /**
     * limit と limit_type は OpenAPI 仕様どおり独立した optional。
     * limit_type=none は期限なしを表し limit を要さないため、両者の
     * クロスバリデーションは行わずサーバー側仕様に委ねる。
     *
     * @param  array<int, int>  $toIds
     *
     * @throws ChatworkValidationException 本文の長さ、to_ids リスト、または limit の値が不正な場合。
     */
    public function __construct(
        public string $body,
        public array $toIds,
        public ?int $limit = null,
        public ?LimitType $limitType = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{body: string, to_ids: string, limit?: positive-int, limit_type?: string}
     */
    public function toArray(): array
    {
        $payload = [
            'body' => $this->body,
            'to_ids' => self::idsToCsv($this->toIds),
        ];

        if ($this->limit !== null) {
            $payload['limit'] = $this->limit;
        }
        if ($this->limitType !== null) {
            $payload['limit_type'] = $this->limitType->value;
        }

        return $payload;
    }

    private function validate(): void
    {
        $length = mb_strlen($this->body);
        if ($length < self::BODY_MIN) {
            throw new ChatworkValidationException(
                'Task body must not be empty.',
                ['body' => ['must not be empty']],
            );
        }
        if ($length > self::BODY_MAX) {
            throw new ChatworkValidationException(
                sprintf('Task body must be %d characters or less.', self::BODY_MAX),
                ['body' => [sprintf('must be %d characters or less', self::BODY_MAX)]],
            );
        }

        if (count($this->toIds) === 0) {
            throw new ChatworkValidationException(
                'to_ids must contain at least one account id.',
                ['to_ids' => ['must contain at least one account id']],
            );
        }
        self::assertIntegerList($this->toIds, 'to_ids');

        if ($this->limit !== null && $this->limit <= 0) {
            throw new ChatworkValidationException(
                'limit must be a positive Unix timestamp.',
                ['limit' => ['must be a positive Unix timestamp']],
            );
        }
    }
}
