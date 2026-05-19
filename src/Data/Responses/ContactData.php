<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class ContactData implements MapsFromArray
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
     * @param  array{
     *     account_id?: int|string,
     *     room_id?: int|string,
     *     name?: string,
     *     chatwork_id?: string,
     *     organization_id?: int|string,
     *     organization_name?: string,
     *     department?: string,
     *     avatar_image_url?: string
     * }  $data
     */
    public static function fromArray(array $data): static
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
