<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use TrustMedical\LaravelChatworkApi\ChatworkServiceProvider;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ChatworkServiceProvider::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Chatwork' => Chatwork::class,
        ];
    }

    protected function fixture(string $relativePath): string
    {
        $path = __DIR__ . '/Fixtures/chatwork/' . ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new \RuntimeException("Fixture not found: {$path}");
        }

        return (string) file_get_contents($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixtureJson(string $relativePath): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($this->fixture($relativePath), associative: true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
