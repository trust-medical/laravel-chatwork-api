<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('メッセージを公開する', function () {
    $exception = new ChatworkValidationException('body is required');

    expect($exception->getMessage())->toBe('body is required');
});

it('violations 配列を返す', function () {
    $exception = new ChatworkValidationException(
        'request payload is invalid',
        ['body' => ['must not be empty', 'must be 65535 chars or less']],
    );

    expect($exception->violations())->toBe([
        'body' => ['must not be empty', 'must be 65535 chars or less'],
    ]);
});

it('violations を省略した場合は空の配列をデフォルト値とする', function () {
    $exception = new ChatworkValidationException('only a message');

    expect($exception->violations())->toBe([]);
});
