<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class CreatedTask implements MapsFromArray
{
    /**
     * @param  list<int>  $taskIds
     */
    public function __construct(
        public array $taskIds,
    ) {}

    /**
     * @param  array{task_ids?: list<int|string>}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            taskIds: self::toIntList($data['task_ids'] ?? []),
        );
    }

    /**
     * @return list<int>
     */
    private static function toIntList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $id): int => (int) $id, $value));
    }
}
