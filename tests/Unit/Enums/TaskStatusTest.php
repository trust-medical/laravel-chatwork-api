<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('is a string-backed enum with the two Chatwork task states', function () {
    expect(TaskStatus::Open->value)->toBe('open');
    expect(TaskStatus::Done->value)->toBe('done');
});

it('resolves a known status with from()', function () {
    expect(TaskStatus::from('done'))->toBe(TaskStatus::Done);
});

it('returns null from tryFrom() for an unknown status', function () {
    expect(TaskStatus::tryFrom('archived'))->toBeNull();
});
