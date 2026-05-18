<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\MarkAsUnreadRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('空の message_id を拒否する', function () {
    $caught = null;
    try {
        new MarkAsUnreadRequest('');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('空でない message_id を受け入れる', function () {
    $request = new MarkAsUnreadRequest('42');

    expect($request->messageId)->toBe('42');
});

it('message_id を toArray で公開する', function () {
    $request = new MarkAsUnreadRequest('42');

    expect($request->toArray())->toBe(['message_id' => '42']);
});
