<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;

it('maps message_id from array', function () {
    $dto = CreatedMessage::fromArray(['message_id' => '1024']);

    expect($dto->messageId)->toBe('1024');
});

it('coerces numeric message_id to string', function () {
    $dto = CreatedMessage::fromArray(['message_id' => 1024]);

    expect($dto->messageId)->toBe('1024');
});

it('is a readonly class', function () {
    expect(CreatedMessage::class)->toBeReadonly();
});
