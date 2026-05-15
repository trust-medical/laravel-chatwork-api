<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

expect()->extend('toBeReadonly', function () {
    /** @var \Pest\Expectation $this */
    $reflection = new ReflectionClass($this->value);

    return expect($reflection->isReadOnly())->toBeTrue(
        "Expected {$reflection->getName()} to be a readonly class."
    );
});
