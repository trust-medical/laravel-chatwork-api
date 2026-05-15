<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('accepts a body and exposes it via toArray', function () {
    $request = new CreateMessageRequest(body: 'Hello, Chatwork!');

    expect($request->toArray())->toBe(['body' => 'Hello, Chatwork!']);
});

it('converts selfUnread=true to self_unread=1 in payload', function () {
    $request = new CreateMessageRequest(body: 'Hi', selfUnread: true);

    expect($request->toArray())->toBe(['body' => 'Hi', 'self_unread' => 1]);
});

it('converts selfUnread=false to self_unread=0 in payload', function () {
    $request = new CreateMessageRequest(body: 'Hi', selfUnread: false);

    expect($request->toArray())->toBe(['body' => 'Hi', 'self_unread' => 0]);
});

it('omits self_unread when selfUnread is null', function () {
    $request = new CreateMessageRequest(body: 'Hi');

    expect($request->toArray())->toHaveKey('body')
        ->and($request->toArray())->not->toHaveKey('self_unread');
});

it('rejects empty body with ChatworkValidationException', function () {
    new CreateMessageRequest(body: '');
})->throws(ChatworkValidationException::class);

it('rejects body over 65535 characters with ChatworkValidationException', function () {
    $body = str_repeat('a', 65536);
    new CreateMessageRequest(body: $body);
})->throws(ChatworkValidationException::class);

it('accepts a 65535-character body as a boundary value', function () {
    $body = str_repeat('a', 65535);
    $request = new CreateMessageRequest(body: $body);

    expect($request->body)->toBe($body);
});

it('counts multibyte characters with mb_strlen (not bytes)', function () {
    // 「あ」は3バイトだが mb_strlen では1文字。
    // 65535 文字の multibyte 本文を作成しても通る。
    $body = str_repeat('あ', 65535);
    $request = new CreateMessageRequest(body: $body);

    expect(mb_strlen($request->body))->toBe(65535);
});

it('exposes violations array on the exception', function () {
    $exception = null;

    try {
        new CreateMessageRequest(body: '');
    } catch (ChatworkValidationException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ChatworkValidationException::class);
    expect($exception?->violations())->toHaveKey('body');
});
