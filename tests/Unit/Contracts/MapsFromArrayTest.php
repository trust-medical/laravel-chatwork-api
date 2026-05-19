<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

/** @var list<string> $dtoClasses */
$dtoClasses = collect(glob(dirname(__DIR__, 3) . '/src/Data/Responses/*.php') ?: [])
    ->map(fn (string $path): string => 'TrustMedical\\LaravelChatworkApi\\Data\\Responses\\' . basename($path, '.php'))
    ->values()
    ->all();

it('Response DTO を 24 個以上検出している（データセット空振り防止）', function () use ($dtoClasses) {
    expect(count($dtoClasses))->toBeGreaterThanOrEqual(24);
});

it('全 Response DTO は MapsFromArray を実装し fromArray が static を返す', function (string $class) {
    expect(class_exists($class))->toBeTrue();
    expect(is_a($class, MapsFromArray::class, true))->toBeTrue();

    $returnType = (new ReflectionMethod($class, 'fromArray'))->getReturnType();

    expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
    /** @var ReflectionNamedType $returnType */
    expect($returnType->getName())->toBe('static');
})->with($dtoClasses);
