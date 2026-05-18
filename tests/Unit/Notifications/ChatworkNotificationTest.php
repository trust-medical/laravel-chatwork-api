<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;

it('サブクラスに toChatwork() の実装を強制する', function () {
    $reflection = new ReflectionClass(ChatworkNotification::class);
    $method = $reflection->getMethod('toChatwork');

    expect($reflection->isAbstract())->toBeTrue()
        ->and($method->isAbstract())->toBeTrue();
});

it('via() から [ChatworkChannel::class] を返す', function () {
    $notification = new class() extends ChatworkNotification
    {
        public function toChatwork(object $notifiable): ChatworkMessage
        {
            return new ChatworkMessage('test');
        }
    };

    expect($notification->via(new stdClass()))->toBe([ChatworkChannel::class]);
});

it('サブクラスの toChatwork() がビルダーチェーンした ChatworkMessage を返せる', function () {
    $notification = new class() extends ChatworkNotification
    {
        public function toChatwork(object $notifiable): ChatworkMessage
        {
            return ChatworkMessage::make()
                ->title('Subject')
                ->body('Body')
                ->selfUnread();
        }
    };

    $message = $notification->toChatwork(new stdClass());

    expect($message->toPayload())->toBe([
        'body' => "[title]Subject[/title]\nBody",
        'self_unread' => 1,
    ]);
});
