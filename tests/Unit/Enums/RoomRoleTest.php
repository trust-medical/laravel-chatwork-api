<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\RoomRole;

it('is a string-backed enum with the three Chatwork roles', function () {
    expect(RoomRole::Admin->value)->toBe('admin');
    expect(RoomRole::Member->value)->toBe('member');
    expect(RoomRole::Readonly->value)->toBe('readonly');
});

it('resolves a known role with from()', function () {
    expect(RoomRole::from('admin'))->toBe(RoomRole::Admin);
});

it('returns null from tryFrom() for an unknown role', function () {
    expect(RoomRole::tryFrom('owner'))->toBeNull();
});
