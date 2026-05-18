<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;
use TrustMedical\LaravelChatworkApi\Data\Responses\Concerns\ConvertsToIntList;

final readonly class CreatedTask implements MapsFromArray
{
    use ConvertsToIntList;

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
}
