<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomRequest;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('allows all fields to be optional and yields an empty payload', function () {
    $request = new UpdateRoomRequest();

    expect($request->toArray())->toBe([]);
});

it('serializes only provided fields', function () {
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

it('rejects a name longer than 255 characters', function () {
    $caught = null;
    try {
        new UpdateRoomRequest(name: str_repeat('a', 256));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects an empty name when explicitly provided', function () {
    $caught = null;
    try {
        new UpdateRoomRequest(name: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});
