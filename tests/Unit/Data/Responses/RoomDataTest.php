<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedRoom;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedRoom;

it('readonly クラスである', function () {
    expect(RoomData::class)->toBeReadonly();
    expect(CreatedRoom::class)->toBeReadonly();
    expect(UpdatedRoom::class)->toBeReadonly();
});

it('getRoom フィクスチャから description を含む全フィールドをハイドレートする', function () {
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

it('一覧ペイロードで description が省略された場合に null として扱う', function () {
    $rooms = fixtureJson('rooms/list-rooms-200.json');

    $first = RoomData::fromArray($rooms[0]);

    expect($first->description)->toBeNull();
});

it('CreatedRoom と UpdatedRoom を room_id のみでハイドレートする', function () {
    $created = CreatedRoom::fromArray(fixtureJson('rooms/create-room-200.json'));
    $updated = UpdatedRoom::fromArray(fixtureJson('rooms/update-room-200.json'));

    expect($created->roomId)->toBe(1234);
    expect($updated->roomId)->toBe(123);
});
