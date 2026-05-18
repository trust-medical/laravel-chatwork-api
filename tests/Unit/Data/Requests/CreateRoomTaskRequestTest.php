<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects an empty body', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: '', toIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a body longer than 65535 characters', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: str_repeat('a', 65536), toIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects empty toIds', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects zero or negative account ids in toIds', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1, 0, 3]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a non-positive limit timestamp', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1], limit: 0);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a negative limit timestamp', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1], limit: -1);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('serializes body and toIds as CSV', function () {
    $request = new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1, 2, 3]);

    $payload = $request->toArray();

    expect($payload['body'])->toBe('Buy milk');
    expect($payload['to_ids'])->toBe('1,2,3');
});

it('omits optional fields when null', function () {
    $request = new CreateRoomTaskRequest(body: 'Buy milk', toIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKeys(['body', 'to_ids']);
    expect($payload)->not->toHaveKey('limit');
    expect($payload)->not->toHaveKey('limit_type');
});

it('serializes limit and limit_type when provided', function () {
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
