<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects an empty code string', function () {
    $caught = null;
    try {
        new RoomLinkRequest(code: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a code longer than 50 characters', function () {
    $caught = null;
    try {
        new RoomLinkRequest(code: str_repeat('a', 51));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('accepts a code of exactly 50 characters', function () {
    $request = new RoomLinkRequest(code: str_repeat('a', 50));

    expect($request->toArray()['code'])->toBe(str_repeat('a', 50));
});

it('serializes needAcceptance bool to 1 or 0', function () {
    expect((new RoomLinkRequest(needAcceptance: true))->toArray()['need_acceptance'])->toBe(1);
    expect((new RoomLinkRequest(needAcceptance: false))->toArray()['need_acceptance'])->toBe(0);
});

it('omits all fields when nothing is provided', function () {
    expect((new RoomLinkRequest())->toArray())->toBe([]);
});

it('omits optional fields that are null', function () {
    $payload = (new RoomLinkRequest(description: 'hello'))->toArray();

    expect($payload)->toBe(['description' => 'hello']);
    expect($payload)->not->toHaveKey('code');
    expect($payload)->not->toHaveKey('need_acceptance');
});
