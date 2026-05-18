<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class IncomingRequestData
{
    public function __construct(
        public int $requestId,
        public int $accountId,
        public string $message,
        public string $name,
        public string $chatworkId,
        public int $organizationId,
        public string $organizationName,
        public string $department,
        public string $avatarImageUrl,
    ) {}

    /**
     * @param  array{
     *     request_id?: int|string,
     *     account_id?: int|string,
     *     message?: string,
     *     name?: string,
     *     chatwork_id?: string,
     *     organization_id?: int|string,
     *     organization_name?: string,
     *     department?: string,
     *     avatar_image_url?: string
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            requestId: (int) ($data['request_id'] ?? 0),
            accountId: (int) ($data['account_id'] ?? 0),
            message: (string) ($data['message'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            chatworkId: (string) ($data['chatwork_id'] ?? ''),
            organizationId: (int) ($data['organization_id'] ?? 0),
            organizationName: (string) ($data['organization_name'] ?? ''),
            department: (string) ($data['department'] ?? ''),
            avatarImageUrl: (string) ($data['avatar_image_url'] ?? ''),
        );
    }
}
