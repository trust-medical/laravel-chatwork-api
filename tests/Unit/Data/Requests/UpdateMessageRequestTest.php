<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateMessageRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の body を拒否する', function () {
    $caught = null;
    try {
        new UpdateMessageRequest('');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('65535 文字を超える body を拒否する', function () {
    $body = str_repeat('a', 65536);

    $caught = null;
    try {
        new UpdateMessageRequest($body);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('有効な body を受け入れる', function () {
    $request = new UpdateMessageRequest('hello');

    expect($request->body)->toBe('hello');
});

it('フォームエンコード送信のために body を toArray で公開する', function () {
    $request = new UpdateMessageRequest('updated body');

    expect($request->toArray())->toBe(['body' => 'updated body']);
});
