<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\ReplacedRoomMembers;

it('is a readonly class', function () {
    expect(ReplacedRoomMembers::class)->toBeReadonly();
});

it('hydrates the three id lists via fromArray', function () {
    $data = fixtureJson('members/replace-room-members-200.json');

    $result = ReplacedRoomMembers::fromArray($data);

    expect($result->admin)->toBe([123, 456]);
    expect($result->member)->toBe([789]);
    expect($result->readonly)->toBe([1011]);
});

it('casts numeric ids to int and reindexes', function () {
    $result = ReplacedRoomMembers::fromArray([
        'admin' => ['1', '2'],
        'member' => [],
        'readonly' => ['9'],
    ]);

    expect($result->admin)->toBe([1, 2]);
    expect($result->member)->toBe([]);
    expect($result->readonly)->toBe([9]);
});

it('defaults missing keys to empty arrays without throwing', function () {
    $result = ReplacedRoomMembers::fromArray([]);

    expect($result->admin)->toBe([]);
    expect($result->member)->toBe([]);
    expect($result->readonly)->toBe([]);
});
