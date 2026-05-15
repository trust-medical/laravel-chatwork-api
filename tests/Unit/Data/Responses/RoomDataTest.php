<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedRoom;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedRoom;

it('is a readonly class', function () {
    expect(RoomData::class)->toBeReadonly();
    expect(CreatedRoom::class)->toBeReadonly();
    expect(UpdatedRoom::class)->toBeReadonly();
});

it('hydrates all fields including description from getRoom fixture', function () {
    $room = RoomData::fromArray(fixtureJson('rooms/get-room-200.json'));

    expect($room->roomId)->toBe(123);
    expect($room->name)->toBe('Group Chat Name');
    expect($room->type)->toBe('group');
    expect($room->role)->toBe('admin');
    expect($room->sticky)->toBeFalse();
    expect($room->unreadNum)->toBe(10);
    expect($room->mentionNum)->toBe(1);
    expect($room->myTaskNum)->toBe(0);
    expect($room->messageNum)->toBe(122);
    expect($room->fileNum)->toBe(10);
    expect($room->taskNum)->toBe(17);
    expect($room->iconPath)->toBe('https://example.com/icon/group.png');
    expect($room->lastUpdateTime)->toBe(1735707600);
    expect($room->description)->toBe('Group description text');
});

it('treats description as null when omitted from list payload', function () {
    $rooms = fixtureJson('rooms/list-rooms-200.json');

    $first = RoomData::fromArray($rooms[0]);

    expect($first->description)->toBeNull();
});

it('hydrates CreatedRoom and UpdatedRoom with room_id only', function () {
    $created = CreatedRoom::fromArray(fixtureJson('rooms/create-room-200.json'));
    $updated = UpdatedRoom::fromArray(fixtureJson('rooms/update-room-200.json'));

    expect($created->roomId)->toBe(1234);
    expect($updated->roomId)->toBe(123);
});
