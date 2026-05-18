<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\RoomRole;

it('readonly クラスである', function () {
    expect(RoomMemberData::class)->toBeReadonly();
});

it('fromArray で RoomMemberData をハイドレートする', function () {
    $data = fixtureJson('members/list-room-members-200.json');

    $member = RoomMemberData::fromArray($data[0]);

    expect($member->accountId)->toBe(123);
    expect($member->role)->toBe(RoomRole::Admin);
    expect($member->name)->toBe('Alice Admin');
    expect($member->chatworkId)->toBe('alice');
    expect($member->organizationId)->toBe(101);
    expect($member->organizationName)->toBe('Example Corp');
    expect($member->department)->toBe('Engineering');
    expect($member->avatarImageUrl)->toBe('https://example.com/avatar/123.png');
});

it('各 role 文字列を RoomRole enum にマッピングする', function () {
    $data = fixtureJson('members/list-room-members-200.json');

    expect(RoomMemberData::fromArray($data[0])->role)->toBe(RoomRole::Admin);
    expect(RoomMemberData::fromArray($data[1])->role)->toBe(RoomRole::Member);
    expect(RoomMemberData::fromArray($data[2])->role)->toBe(RoomRole::Readonly);
});

it('不明な role の場合に例外なく RoomRole::Member にフォールバックする', function () {
    $member = RoomMemberData::fromArray([
        'account_id' => 1,
        'role' => 'unexpected',
        'name' => 'X',
    ]);

    expect($member->role)->toBe(RoomRole::Member);
});

it('数値の account_id を int にキャストする', function () {
    $member = RoomMemberData::fromArray([
        'account_id' => '42',
        'role' => 'member',
    ]);

    expect($member->accountId)->toBe(42);
});
