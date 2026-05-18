<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class CreateRoomTaskRequest
{
    private const int BODY_MIN = 1;

    private const int BODY_MAX = 65535;

    /**
     * @param  array<int, int>  $toIds
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
     * @return array<string, mixed>
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

    /**
     * @param  array<int, mixed>  $ids
     */
    private static function assertIntegerList(array $ids, string $field): void
    {
        foreach ($ids as $id) {
            if (! is_int($id) || $id <= 0) {
                throw new ChatworkValidationException(
                    sprintf('%s must contain positive integers only.', $field),
                    [$field => ['must contain positive integers only']],
                );
            }
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    private static function idsToCsv(array $ids): string
    {
        return implode(',', $ids);
    }
}
