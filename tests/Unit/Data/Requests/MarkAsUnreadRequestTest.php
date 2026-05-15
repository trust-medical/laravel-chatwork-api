<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\MarkAsUnreadRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects an empty message_id', function () {
    $caught = null;
    try {
        new MarkAsUnreadRequest('');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('accepts a non-empty message_id', function () {
    $request = new MarkAsUnreadRequest('42');

    expect($request->messageId)->toBe('42');
});

it('exposes message_id via toArray', function () {
    $request = new MarkAsUnreadRequest('42');

    expect($request->toArray())->toBe(['message_id' => '42']);
});
