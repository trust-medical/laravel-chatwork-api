<?php

declare(strict_types=1);

use Pest\Expectation;
use TrustMedical\LaravelChatworkApi\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

expect()->extend('toBeReadonly', function () {
    /** @var Expectation $this */
    $value = $this->value;
    $reflection = new ReflectionClass(is_object($value) ? $value : (string) $value);

    return expect($reflection->isReadOnly())->toBeTrue(
        "{$reflection->getName()} は readonly クラスであるべきです。"
    );
});
