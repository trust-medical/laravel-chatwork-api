<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Composer\InstalledVersions;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use OutOfBoundsException;
use TrustMedical\LaravelChatworkApi\Connection;

final class ChatworkPendingRequestFactory
{
    public function create(Connection $connection): PendingRequest
    {
        $pending = Http::baseUrl($connection->baseUri)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => self::userAgent(),
            ])
            ->timeout($connection->timeout);

        return $connection->credentials->applyTo($pending);
    }

    private static function userAgent(): string
    {
        return sprintf(
            'trust-medical/laravel-chatwork-api/%s Laravel/%s PHP/%s',
            self::tryVersion('trust-medical/laravel-chatwork-api'),
            self::tryVersion('laravel/framework'),
            PHP_VERSION,
        );
    }

    private static function tryVersion(string $package): string
    {
        try {
            $version = InstalledVersions::getPrettyVersion($package);
        } catch (OutOfBoundsException) {
            return 'dev';
        }

        return $version ?? 'dev';
    }
}
