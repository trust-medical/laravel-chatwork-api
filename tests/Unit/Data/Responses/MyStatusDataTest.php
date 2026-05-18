<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;

it('is a readonly class', function () {
    expect(MyStatusData::class)->toBeReadonly();
});

it('hydrates MyStatusData via fromArray', function () {
    $status = MyStatusData::fromArray(fixtureJson('my/get-my-status-200.json'));

    expect($status->unreadRoomNum)->toBe(2);
    expect($status->mentionRoomNum)->toBe(1);
    expect($status->mytaskRoomNum)->toBe(3);
    expect($status->unreadNum)->toBe(12);
    expect($status->mentionNum)->toBe(4);
    expect($status->mytaskNum)->toBe(7);
});

it('casts numeric strings to int', function () {
    $status = MyStatusData::fromArray([
        'unread_room_num' => '5',
        'unread_num' => '50',
    ]);

    expect($status->unreadRoomNum)->toBe(5);
    expect($status->unreadNum)->toBe(50);
});

it('falls back to 0 for missing fields without throwing', function () {
    $status = MyStatusData::fromArray([]);

    expect($status->unreadRoomNum)->toBe(0);
    expect($status->mentionRoomNum)->toBe(0);
    expect($status->mytaskRoomNum)->toBe(0);
    expect($status->unreadNum)->toBe(0);
    expect($status->mentionNum)->toBe(0);
    expect($status->mytaskNum)->toBe(0);
});
