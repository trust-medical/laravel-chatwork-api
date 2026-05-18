<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('body を受け取り toArray で公開する', function () {
    $request = new CreateMessageRequest(body: 'Hello, Chatwork!');

    expect($request->toArray())->toBe(['body' => 'Hello, Chatwork!']);
});

it('selfUnread=true を payload の self_unread=1 に変換する', function () {
    $request = new CreateMessageRequest(body: 'Hi', selfUnread: true);

    expect($request->toArray())->toBe(['body' => 'Hi', 'self_unread' => 1]);
});

it('selfUnread=false を payload の self_unread=0 に変換する', function () {
    $request = new CreateMessageRequest(body: 'Hi', selfUnread: false);

    expect($request->toArray())->toBe(['body' => 'Hi', 'self_unread' => 0]);
});

it('selfUnread が null のとき self_unread を省略する', function () {
    $request = new CreateMessageRequest(body: 'Hi');

    expect($request->toArray())->toHaveKey('body')
        ->and($request->toArray())->not->toHaveKey('self_unread');
});

it('空の body を ChatworkValidationException で拒否する', function () {
    new CreateMessageRequest(body: '');
})->throws(ChatworkValidationException::class);

it('65535 文字を超える body を ChatworkValidationException で拒否する', function () {
    $body = str_repeat('a', 65536);
    new CreateMessageRequest(body: $body);
})->throws(ChatworkValidationException::class);

it('65535 文字の body を境界値として受け入れる', function () {
    $body = str_repeat('a', 65535);
    $request = new CreateMessageRequest(body: $body);

    expect($request->body)->toBe($body);
});

it('マルチバイト文字を（バイトでなく）mb_strlen で数える', function () {
    // 「あ」は3バイトだが mb_strlen では1文字。
    // 65535 文字の multibyte 本文を作成しても通る。
    $body = str_repeat('あ', 65535);
    $request = new CreateMessageRequest(body: $body);

    expect(mb_strlen($request->body))->toBe(65535);
});

it('例外が violations 配列を公開する', function () {
    $exception = null;

    try {
        new CreateMessageRequest(body: '');
    } catch (ChatworkValidationException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ChatworkValidationException::class);
    expect($exception?->violations())->toHaveKey('body');
});
