<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomRequest;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('すべてのフィールドをオプションにでき、空の payload を返す', function () {
    $request = new UpdateRoomRequest();

    expect($request->toArray())->toBe([]);
});

it('指定されたフィールドのみをシリアライズする', function () {
    $request = new UpdateRoomRequest(
        name: 'New Name',
        description: 'desc',
        iconPreset: IconPreset::Study,
    );

    expect($request->toArray())->toBe([
        'name' => 'New Name',
        'description' => 'desc',
        'icon_preset' => 'study',
    ]);
});

it('255 文字を超える name を拒否する', function () {
    $caught = null;
    try {
        new UpdateRoomRequest(name: str_repeat('a', 256));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('明示的に空の name が指定された場合に拒否する', function () {
    $caught = null;
    try {
        new UpdateRoomRequest(name: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});
