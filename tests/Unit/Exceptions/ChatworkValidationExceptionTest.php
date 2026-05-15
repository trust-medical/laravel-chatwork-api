<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('exposes the message', function () {
    $exception = new ChatworkValidationException('body is required');

    expect($exception->getMessage())->toBe('body is required');
});

it('returns the violations array', function () {
    $exception = new ChatworkValidationException(
        'request payload is invalid',
        ['body' => ['must not be empty', 'must be 65535 chars or less']],
    );

    expect($exception->violations())->toBe([
        'body' => ['must not be empty', 'must be 65535 chars or less'],
    ]);
});

it('defaults to empty violations when omitted', function () {
    $exception = new ChatworkValidationException('only a message');

    expect($exception->violations())->toBe([]);
});
