<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use TrustMedical\LaravelChatworkApi\Auth\Credentials;

/**
 * Chatwork connection のアイデンティティ・credentials・base URI・タイムアウトを統一する
 * イミュータブルな値オブジェクト。config・DB・動的解決済みトークンのどれが起源かを問わない。
 * name + credentials が同じであれば同一とみなせる。
 */
final readonly class Connection
{
    public function __construct(
        public string $name,
        public Credentials $credentials,
        public string $baseUri = 'https://api.chatwork.com/v2',
        public int $timeout = 10,
    ) {}

    public static function make(
        string $name,
        Credentials $credentials,
        ?string $baseUri = null,
        ?int $timeout = null,
    ): self {
        return new self(
            name: $name,
            credentials: $credentials,
            baseUri: $baseUri ?? 'https://api.chatwork.com/v2',
            timeout: $timeout ?? 10,
        );
    }
}
