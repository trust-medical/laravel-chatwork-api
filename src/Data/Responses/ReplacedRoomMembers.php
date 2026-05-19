<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;
use TrustMedical\LaravelChatworkApi\Data\Responses\Concerns\ConvertsToIntList;

final readonly class ReplacedRoomMembers implements MapsFromArray
{
    use ConvertsToIntList;

    /**
     * @param  list<int>  $admin
     * @param  list<int>  $member
     * @param  list<int>  $readonly
     */
    public function __construct(
        public array $admin,
        public array $member,
        public array $readonly,
    ) {}

    /**
     * @param  array{admin?: mixed, member?: mixed, readonly?: mixed}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            admin: self::toIntList($data['admin'] ?? []),
            member: self::toIntList($data['member'] ?? []),
            readonly: self::toIntList($data['readonly'] ?? []),
        );
    }
}
