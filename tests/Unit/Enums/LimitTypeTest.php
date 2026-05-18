<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\LimitType;

it('is a string-backed enum with the three Chatwork limit types', function () {
    expect(LimitType::None->value)->toBe('none');
    expect(LimitType::Date->value)->toBe('date');
    expect(LimitType::Time->value)->toBe('time');
});

it('resolves a known limit type with from()', function () {
    expect(LimitType::from('time'))->toBe(LimitType::Time);
});

it('returns null from tryFrom() for an unknown limit type', function () {
    expect(LimitType::tryFrom('week'))->toBeNull();
});
