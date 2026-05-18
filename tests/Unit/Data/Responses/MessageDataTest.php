<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;

it('readonly クラスである', function () {
    expect(MessageData::class)->toBeReadonly();
    expect(SimpleAccount::class)->toBeReadonly();
});

it('fromArray でネストした SimpleAccount を含む MessageData をハイドレートする', function () {
    $data = fixtureJson('messages/get-message-200.json');

    $message = MessageData::fromArray($data);

    expect($message->messageId)->toBe('5');
    expect($message->body)->toBe('Hello, Chatwork!');
    expect($message->sendTime)->toBe(1735707600);
    expect($message->updateTime)->toBe(0);
    expect($message->account)->toBeInstanceOf(SimpleAccount::class);
    expect($message->account->accountId)->toBe(123);
    expect($message->account->name)->toBe('Bob');
    expect($message->account->avatarImageUrl)->toBe('https://example.com/avatar/bob.png');
});

it('数値の message_id を string にキャストする', function () {
    $message = MessageData::fromArray([
        'message_id' => 42,
        'account' => ['account_id' => 1, 'name' => 'X', 'avatar_image_url' => ''],
        'body' => 'hi',
        'send_time' => 1,
        'update_time' => 0,
    ]);

    expect($message->messageId)->toBe('42');
});
