<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の body を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: '', toIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('65535 文字を超える body を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: str_repeat('a', 65536), toIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('空の toIds を拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('toIds に 0 以下のアカウント ID が含まれる場合に拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('0 以下の limit タイムスタンプを拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1], limit: 0);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('負の limit タイムスタンプを拒否する', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1], limit: -1);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('body と toIds を CSV にシリアライズする', function () {
    $request = new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1, 2, 3]);

    $payload = $request->toArray();

    expect($payload['body'])->toBe('Buy milk');
    expect($payload['to_ids'])->toBe('1,2,3');
});

it('null のオプションフィールドを省略する', function () {
    $request = new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKeys(['body', 'to_ids']);
    expect($payload)->not->toHaveKey('limit');
    expect($payload)->not->toHaveKey('limit_type');
});

it('指定された場合に limit と limit_type をシリアライズする', function () {
    $request = new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1],
        limit: 1735707600,
        limitType: LimitType::Date,
    );

    $payload = $request->toArray();

    expect($payload['limit'])->toBe(1735707600);
    expect($payload['limit_type'])->toBe('date');
});
