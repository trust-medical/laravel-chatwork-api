<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\RoomRole;

it('Chatwork の 3 種類のロールを持つ文字列バック enum である', function () {
    expect(RoomRole::Admin->value)->toBe('admin');
    expect(RoomRole::Member->value)->toBe('member');
    expect(RoomRole::Readonly->value)->toBe('readonly');
});

it('from() で既知のロールを解決できる', function () {
    expect(RoomRole::from('admin'))->toBe(RoomRole::Admin);
});

it('未知のロールに対して tryFrom() が null を返す', function () {
    expect(RoomRole::tryFrom('owner'))->toBeNull();
});
