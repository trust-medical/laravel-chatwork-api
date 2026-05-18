<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-token',
    ]);
});

/**
 * @param  array<int, ChatworkRoute|int|string>  $rooms
 */
function userWithRoomArray(array $rooms): object
{
    return new class($rooms)
    {
        use Notifiable;

        /**
         * @param  array<int, ChatworkRoute|int|string>  $rooms
         */
        public function __construct(private readonly array $rooms) {}

        /**
         * @return array<int, ChatworkRoute|int|string>
         */
        public function routeNotificationForChatwork(): array
        {
            return $this->rooms;
        }
    };
}

it('すべて成功した場合、すべてのルームに順番どおりに送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);

    userWithRoomArray([
        ChatworkRoute::room(11),
        ChatworkRoute::room(22),
        ChatworkRoute::room(33),
    ])->notify(chatworkNotification(new ChatworkMessage('Hi')));

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/11/messages');
    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/22/messages');
    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/33/messages');
});

it('最初の失敗 (4xx) で停止し、以降のルームには送信しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/11/messages' => Http::response(['message_id' => '1'], 201),
        'https://api.chatwork.com/v2/rooms/22/messages' => Http::response(['errors' => ['nope']], 400),
        'https://api.chatwork.com/v2/rooms/33/messages' => Http::response(['message_id' => '3'], 201),
    ]);

    $caught = null;
    try {
        userWithRoomArray([
            ChatworkRoute::room(11),
            ChatworkRoute::room(22),
            ChatworkRoute::room(33),
        ])->notify(chatworkNotification(new ChatworkMessage('Hi')));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(400);

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/11/messages');
    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/22/messages');
    Http::assertNotSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/33/messages');
});

it('5xx の最初の失敗でも停止し、以降のルームには送信しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/11/messages' => Http::response(['message_id' => '1'], 201),
        'https://api.chatwork.com/v2/rooms/22/messages' => Http::response('', 503),
        'https://api.chatwork.com/v2/rooms/33/messages' => Http::response(['message_id' => '3'], 201),
    ]);

    $caught = null;
    try {
        userWithRoomArray([
            ChatworkRoute::room(11),
            ChatworkRoute::room(22),
            ChatworkRoute::room(33),
        ])->notify(chatworkNotification(new ChatworkMessage('Hi')));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(503);

    Http::assertNotSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/33/messages');
});
