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

if (! function_exists('tempUploadFile')) {
    /**
     * Creates a temporary file for upload tests and schedules its removal.
     *
     * When $truncateTo is given a sparse file of exactly that many bytes is
     * created without writing real IO (used for the 5 MiB boundary cases);
     * otherwise $contents is written verbatim.
     */
    function tempUploadFile(string $contents = 'hello world', ?int $truncateTo = null): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'cwfile');
        register_shutdown_function(static fn () => @unlink($path));

        if ($truncateTo !== null) {
            $fp = fopen($path, 'w');
            assert($fp !== false);
            ftruncate($fp, $truncateTo);
            fclose($fp);

            return $path;
        }

        file_put_contents($path, $contents);

        return $path;
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
