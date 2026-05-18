<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class ReplacedRoomMembers
{
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
    public static function fromArray(array $data): self
    {
        return new self(
            admin: self::toIntList($data['admin'] ?? []),
            member: self::toIntList($data['member'] ?? []),
            readonly: self::toIntList($data['readonly'] ?? []),
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
