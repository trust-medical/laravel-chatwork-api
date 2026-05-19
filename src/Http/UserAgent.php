<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * パッケージ共通の User-Agent 文字列ビルダー。
 *
 * Chatwork API ホストと OAuth トークンエンドポイント（別ホスト）の双方で
 * 同一の `trust-medical/laravel-chatwork-api/{ver} Laravel/{ver} PHP/{ver}`
 * を付与するため一元化する。
 */
final class UserAgent
{
    public static function string(): string
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
