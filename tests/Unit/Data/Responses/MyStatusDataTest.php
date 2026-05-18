<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;

it('readonly クラスである', function () {
    expect(MyStatusData::class)->toBeReadonly();
});

it('fromArray で MyStatusData をハイドレートする', function () {
    $status = MyStatusData::fromArray(fixtureJson('my/get-my-status-200.json'));

    expect($status->unreadRoomNum)->toBe(2);
    expect($status->mentionRoomNum)->toBe(1);
    expect($status->mytaskRoomNum)->toBe(3);
    expect($status->unreadNum)->toBe(12);
    expect($status->mentionNum)->toBe(4);
    expect($status->mytaskNum)->toBe(7);
});

it('数値文字列を int にキャストする', function () {
    $status = MyStatusData::fromArray([
        'unread_room_num' => '5',
        'unread_num' => '50',
    ]);

    expect($status->unreadRoomNum)->toBe(5);
    expect($status->unreadNum)->toBe(50);
});

it('フィールドが欠けていても例外を投げずに 0 を返す', function () {
    $status = MyStatusData::fromArray([]);

    expect($status->unreadRoomNum)->toBe(0);
    expect($status->mentionRoomNum)->toBe(0);
    expect($status->mytaskRoomNum)->toBe(0);
    expect($status->unreadNum)->toBe(0);
    expect($status->mentionNum)->toBe(0);
    expect($status->mytaskNum)->toBe(0);
});
