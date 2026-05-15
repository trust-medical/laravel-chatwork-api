<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class SimpleAccount
{
    public function __construct(
        public int $accountId,
        public string $name,
        public string $avatarImageUrl,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: (int) ($data['account_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            avatarImageUrl: (string) ($data['avatar_image_url'] ?? ''),
        );
    }
}
