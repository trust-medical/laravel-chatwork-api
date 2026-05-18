<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleRoom;

it('is a readonly class', function () {
    expect(SimpleRoom::class)->toBeReadonly();
});

it('hydrates SimpleRoom via fromArray', function () {
    $room = SimpleRoom::fromArray([
        'room_id' => 322,
        'name' => 'Group Chat Name',
        'icon_path' => 'https://example.com/icon/group.png',
    ]);

    expect($room->roomId)->toBe(322);
    expect($room->name)->toBe('Group Chat Name');
    expect($room->iconPath)->toBe('https://example.com/icon/group.png');
});

it('casts a numeric room_id string to int', function () {
    expect(SimpleRoom::fromArray(['room_id' => '99'])->roomId)->toBe(99);
});

it('falls back to defaults for missing fields without throwing', function () {
    $room = SimpleRoom::fromArray([]);

    expect($room->roomId)->toBe(0);
    expect($room->name)->toBe('');
    expect($room->iconPath)->toBe('');
});
