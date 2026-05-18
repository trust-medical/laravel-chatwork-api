<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleRoom;

it('readonly クラスである', function () {
    expect(SimpleRoom::class)->toBeReadonly();
});

it('fromArray で SimpleRoom をハイドレートする', function () {
    $room = SimpleRoom::fromArray([
        'room_id' => 322,
        'name' => 'Group Chat Name',
        'icon_path' => 'https://example.com/icon/group.png',
    ]);

    expect($room->roomId)->toBe(322);
    expect($room->name)->toBe('Group Chat Name');
    expect($room->iconPath)->toBe('https://example.com/icon/group.png');
});

it('数値文字列の room_id を int にキャストする', function () {
    expect(SimpleRoom::fromArray(['room_id' => '99'])->roomId)->toBe(99);
});

it('存在しないフィールドを例外なくデフォルト値にフォールバックする', function () {
    $room = SimpleRoom::fromArray([]);

    expect($room->roomId)->toBe(0);
    expect($room->name)->toBe('');
    expect($room->iconPath)->toBe('');
});
