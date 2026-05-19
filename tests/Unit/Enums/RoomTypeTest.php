<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\RoomType;

it('Chatwork の 3 種類のルーム種別を持つ文字列バック enum である', function () {
    expect(RoomType::My->value)->toBe('my');
    expect(RoomType::Direct->value)->toBe('direct');
    expect(RoomType::Group->value)->toBe('group');
});

it('from() で既知の種別を解決できる', function () {
    expect(RoomType::from('group'))->toBe(RoomType::Group);
});

it('未知の種別に対して tryFrom() が null を返す', function () {
    expect(RoomType::tryFrom('channel'))->toBeNull();
});
