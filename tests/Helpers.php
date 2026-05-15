<?php

declare(strict_types=1);

/**
 * Test helpers loaded via `autoload-dev.files` in composer.json.
 *
 * Defined at file scope so PHPStan can resolve them inside Pest closures (where
 * `$this` is bound dynamically and cannot be typed as our TestCase).
 */
if (! function_exists('fixtureJson')) {
    /**
     * Reads a fixture JSON file under tests/Fixtures/chatwork/ and returns it as an array.
     *
     * @return array<int|string, mixed>
     */
    function fixtureJson(string $relativePath): array
    {
        $path = __DIR__ . '/Fixtures/chatwork/' . ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        /** @var array<int|string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($path), associative: true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}

if (! function_exists('fixture')) {
    /**
     * Reads a fixture file under tests/Fixtures/chatwork/ and returns it as a raw string.
     */
    function fixture(string $relativePath): string
    {
        $path = __DIR__ . '/Fixtures/chatwork/' . ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new RuntimeException("Fixture not found: {$path}");
        }

        return (string) file_get_contents($path);
    }
}
