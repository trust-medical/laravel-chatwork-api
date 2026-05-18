<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('Chatwork の 2 種類のタスク状態を持つ文字列バック enum である', function () {
    expect(TaskStatus::Open->value)->toBe('open');
    expect(TaskStatus::Done->value)->toBe('done');
});

it('from() で既知のステータスを解決できる', function () {
    expect(TaskStatus::from('done'))->toBe(TaskStatus::Done);
});

it('未知のステータスに対して tryFrom() が null を返す', function () {
    expect(TaskStatus::tryFrom('archived'))->toBeNull();
});
