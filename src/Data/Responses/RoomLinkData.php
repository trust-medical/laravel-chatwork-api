<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class RoomLinkData
{
    public function __construct(
        public bool $public,
        public string $url,
        public bool $needAcceptance,
        public string $description,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            public: (bool) ($data['public'] ?? false),
            url: (string) ($data['url'] ?? ''),
            needAcceptance: (bool) ($data['need_acceptance'] ?? false),
            description: (string) ($data['description'] ?? ''),
        );
    }
}
