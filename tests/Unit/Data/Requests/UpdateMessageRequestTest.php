<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateMessageRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects an empty body', function () {
    $caught = null;
    try {
        new UpdateMessageRequest('');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a body longer than 65535 characters', function () {
    $body = str_repeat('a', 65536);

    $caught = null;
    try {
        new UpdateMessageRequest($body);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('accepts a valid body', function () {
    $request = new UpdateMessageRequest('hello');

    expect($request->body)->toBe('hello');
});

it('exposes body via toArray for form-encoded submission', function () {
    $request = new UpdateMessageRequest('updated body');

    expect($request->toArray())->toBe(['body' => 'updated body']);
});
