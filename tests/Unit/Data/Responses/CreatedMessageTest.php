<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;

it('配列から message_id をマッピングする', function () {
    $dto = CreatedMessage::fromArray(['message_id' => '1024']);

    expect($dto->messageId)->toBe('1024');
});

it('数値の message_id を string に変換する', function () {
    $dto = CreatedMessage::fromArray(['message_id' => 1024]);

    expect($dto->messageId)->toBe('1024');
});

it('欠損キーは空文字にフォールバックする', function () {
    expect(CreatedMessage::fromArray([])->messageId)->toBe('');
});

it('readonly クラスである', function () {
    expect(CreatedMessage::class)->toBeReadonly();
});
