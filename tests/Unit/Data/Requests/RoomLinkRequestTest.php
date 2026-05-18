<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の code 文字列を拒否する', function () {
    $caught = null;
    try {
        new RoomLinkRequest(code: '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('50 文字を超える code を拒否する', function () {
    $caught = null;
    try {
        new RoomLinkRequest(code: str_repeat('a', 51));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('ちょうど 50 文字の code を受け入れる', function () {
    $request = new RoomLinkRequest(code: str_repeat('a', 50));

    expect($request->toArray()['code'])->toBe(str_repeat('a', 50));
});

it('needAcceptance の bool 値を 1 または 0 にシリアライズする', function () {
    expect((new RoomLinkRequest(needAcceptance: true))->toArray()['need_acceptance'])->toBe(1);
    expect((new RoomLinkRequest(needAcceptance: false))->toArray()['need_acceptance'])->toBe(0);
});

it('何も指定されない場合にすべてのフィールドを省略する', function () {
    expect((new RoomLinkRequest())->toArray())->toBe([]);
});

it('null のオプションフィールドを省略する', function () {
    $payload = (new RoomLinkRequest(description: 'hello'))->toArray();

    expect($payload)->toBe(['description' => 'hello']);
    expect($payload)->not->toHaveKey('code');
    expect($payload)->not->toHaveKey('need_acceptance');
});
