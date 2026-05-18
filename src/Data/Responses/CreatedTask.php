<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class CreatedTask
{
    /**
     * @param  array<int, int>  $taskIds
     */
    public function __construct(
        public array $taskIds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            taskIds: self::toIntList($data['task_ids'] ?? []),
        );
    }

    /**
     * @return array<int, int>
     */
    private static function toIntList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $id): int => (int) $id, $value));
    }
}
