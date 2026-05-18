<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use InvalidArgumentException;
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
        $resolvedBaseUri = $baseUri ?? 'https://api.chatwork.com/v2';

        $scheme = strtolower((string) parse_url($resolvedBaseUri, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new InvalidArgumentException(
                sprintf('Connection baseUri must use the http or https scheme, got "%s".', $resolvedBaseUri),
            );
        }

        return new self(
            name: $name,
            credentials: $credentials,
            baseUri: $resolvedBaseUri,
            timeout: $timeout ?? 10,
        );
    }

    /**
     * var_dump / dd でトークンを保持する credentials を再帰展開せず、
     * クラス名のみを文字列で表す。
     *
     * @return array{name: string, credentials: string, baseUri: string, timeout: int}
     */
    public function __debugInfo(): array
    {
        return [
            'name' => $this->name,
            'credentials' => $this->credentials::class,
            'baseUri' => $this->baseUri,
            'timeout' => $this->timeout,
        ];
    }
}
