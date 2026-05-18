<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\LimitType;

it('Chatwork の 3 種類のリミットタイプを持つ文字列バック enum である', function () {
    expect(LimitType::None->value)->toBe('none');
    expect(LimitType::Date->value)->toBe('date');
    expect(LimitType::Time->value)->toBe('time');
});

it('from() で既知のリミットタイプを解決できる', function () {
    expect(LimitType::from('time'))->toBe(LimitType::Time);
});

it('未知のリミットタイプに対して tryFrom() が null を返す', function () {
    expect(LimitType::tryFrom('week'))->toBeNull();
});
