<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\RoomRole;

it('is a readonly class', function () {
    expect(RoomMemberData::class)->toBeReadonly();
});

it('hydrates RoomMemberData via fromArray', function () {
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

it('maps each role string to the RoomRole enum', function () {
    $data = fixtureJson('members/list-room-members-200.json');

    expect(RoomMemberData::fromArray($data[0])->role)->toBe(RoomRole::Admin);
    expect(RoomMemberData::fromArray($data[1])->role)->toBe(RoomRole::Member);
    expect(RoomMemberData::fromArray($data[2])->role)->toBe(RoomRole::Readonly);
});

it('falls back to RoomRole::Member for an unknown role without throwing', function () {
    $member = RoomMemberData::fromArray([
        'account_id' => 1,
        'role' => 'unexpected',
        'name' => 'X',
    ]);

    expect($member->role)->toBe(RoomRole::Member);
});

it('casts numeric account_id to int', function () {
    $member = RoomMemberData::fromArray([
        'account_id' => '42',
        'role' => 'member',
    ]);

    expect($member->accountId)->toBe(42);
});
