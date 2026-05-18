<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\ReplacedRoomMembers;

it('readonly クラスである', function () {
    expect(ReplacedRoomMembers::class)->toBeReadonly();
});

it('fromArray で3つの id リストをハイドレートする', function () {
    $data = fixtureJson('members/replace-room-members-200.json');

    $result = ReplacedRoomMembers::fromArray($data);

    expect($result->admin)->toBe([123, 456]);
    expect($result->member)->toBe([789]);
    expect($result->readonly)->toBe([1011]);
});

it('数値 id を int にキャストして再インデックスする', function () {
    $result = ReplacedRoomMembers::fromArray([
        'admin' => ['1', '2'],
        'member' => [],
        'readonly' => ['9'],
    ]);

    expect($result->admin)->toBe([1, 2]);
    expect($result->member)->toBe([]);
    expect($result->readonly)->toBe([9]);
});

it('存在しないキーを例外なく空配列にデフォルトする', function () {
    $result = ReplacedRoomMembers::fromArray([]);

    expect($result->admin)->toBe([]);
    expect($result->member)->toBe([]);
    expect($result->readonly)->toBe([]);
});
