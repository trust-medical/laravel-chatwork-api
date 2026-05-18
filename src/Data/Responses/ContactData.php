<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class ContactData
{
    public function __construct(
        public int $accountId,
        public int $roomId,
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
            roomId: (int) ($data['room_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            chatworkId: (string) ($data['chatwork_id'] ?? ''),
            organizationId: (int) ($data['organization_id'] ?? 0),
            organizationName: (string) ($data['organization_name'] ?? ''),
            department: (string) ($data['department'] ?? ''),
            avatarImageUrl: (string) ($data['avatar_image_url'] ?? ''),
        );
    }
}
