<?php

declare(strict_types=1);

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-token',
    ]);
});

function withChatworkUserHavingRoom(int|string $roomId): object
{
    return new class($roomId)
    {
        use Notifiable;

        public function __construct(private readonly int|string $roomId) {}

        public function routeNotificationForChatwork(): int|string
        {
            return $this->roomId;
        }
    };
}

it('toRoom() と routeNotificationForChatwork() が競合する場合は ChatworkRoutingException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    $caught = null;
    try {
        withChatworkUserHavingRoom(111)->notify(
            ChatworkMessage::make()->body('Hi')->toRoom(999),
        );
    } catch (ChatworkRoutingException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRoutingException::class)
        ->and($caught?->violations())->toHaveKey('route');

    Http::assertNothingSent();
});

it('routeNotificationForChatwork が int の配列を返す場合、すべてのルームに送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    $user = new class()
    {
        use Notifiable;

        /**
         * @return array<int, int>
         */
        public function routeNotificationForChatwork(): array
        {
            return [101, 102];
        }
    };

    $user->notify(new ChatworkMessage('Hi'));

    Http::assertSentCount(2);
});

it('ChatworkRoute の配列が返された場合、各 ChatworkRoute に送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    $user = new class()
    {
        use Notifiable;

        /**
         * @return array<int, ChatworkRoute>
         */
        public function routeNotificationForChatwork(): array
        {
            return [
                ChatworkRoute::room(201),
                ChatworkRoute::room(202),
            ];
        }
    };

    $user->notify(new ChatworkMessage('Hi'));

    Http::assertSentCount(2);
});

it('サポートされていないルート型に対して ChatworkRoutingException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    $caught = null;
    try {
        Notification::route('chatwork', new stdClass())->notify(new ChatworkMessage('Hi'));
    } catch (ChatworkRoutingException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRoutingException::class);
});

it('notifiable のルートが未指定の場合は toRoom() を優先する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    $user = new class()
    {
        use Notifiable;

        public function routeNotificationForChatwork(): null
        {
            return null;
        }
    };

    $user->notify(ChatworkMessage::make()->body('Hi')->toRoom(777));

    Http::assertSent(fn ($r) => $r->url() === 'https://api.chatwork.com/v2/rooms/777/messages');
});
