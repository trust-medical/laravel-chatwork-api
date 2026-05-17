<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Enums\RoomRole;

final readonly class RoomMemberData
{
    public function __construct(
        public int $accountId,
        public RoomRole $role,
        public string $name,
        public string $chatworkId,
        public int $organizationId,
        public string $organizationName,
        public string $department,
        public string $avatarImageUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: (int) ($data['account_id'] ?? 0),
            // fromArray は throw しない方針。未知 role でも hydrate を止めない。
            role: RoomRole::tryFrom((string) ($data['role'] ?? '')) ?? RoomRole::Member,
            name: (string) ($data['name'] ?? ''),
            chatworkId: (string) ($data['chatwork_id'] ?? ''),
            organizationId: (int) ($data['organization_id'] ?? 0),
            organizationName: (string) ($data['organization_name'] ?? ''),
            department: (string) ($data['department'] ?? ''),
            avatarImageUrl: (string) ($data['avatar_image_url'] ?? ''),
        );
    }
}
