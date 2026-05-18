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
     * Chatwork は通常 bool を返すが、過去の挙動互換のため int(0/1) も受理して bool へ正規化する。
     *
     * @param  array{
     *     public?: bool|int,
     *     url?: string,
     *     need_acceptance?: bool|int,
     *     description?: string
     * }  $data
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
