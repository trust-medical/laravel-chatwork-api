<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-token',
    ]);
    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'sales-token',
    ]);
});

function makeChatworkUser(int|string|null $roomId): object
{
    return new class($roomId)
    {
        use Notifiable;

        public function __construct(private readonly int|string|null $chatworkRoomId) {}

        public function routeNotificationForChatwork(): int|string|null
        {
            return $this->chatworkRoomId;
        }
    };
}

function fakeChatworkOk(): void
{
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['message_id' => '1'], 201),
    ]);
}

it('sends to routeNotificationForChatwork target via $user->notify(new ChatworkMessage)', function () {
    fakeChatworkOk();

    makeChatworkUser(111)->notify(new ChatworkMessage('Hello'));

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/111/messages'
        && $r['body'] === 'Hello'
    );
});

it('sends to Notification::route(\'chatwork\', $roomId)', function () {
    fakeChatworkOk();

    Notification::route('chatwork', 222)->notify(new ChatworkMessage('Hi'));

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/222/messages');
});

it('honours ChatworkRoute::room()->connection() for named connection', function () {
    fakeChatworkOk();

    $route = ChatworkRoute::room(333)->connection('sales');
    Notification::route('chatwork', $route)->notify(new ChatworkMessage('Hi'));

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/333/messages'
        && $r->hasHeader('Authorization', 'Bearer sales-token')
    );
});

it('honours ChatworkRoute::room()->using(Connection) for ad-hoc connection', function () {
    fakeChatworkOk();

    $connection = Connection::make('dynamic', new BearerTokenCredentials('dynamic-bearer'));
    $route = ChatworkRoute::room(444)->using($connection);

    Notification::route('chatwork', $route)->notify(new ChatworkMessage('Hi'));

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/444/messages'
        && $r->hasHeader('Authorization', 'Bearer dynamic-bearer')
    );
});

it('uses ChatworkMessage::toRoom() as the override target', function () {
    fakeChatworkOk();

    makeChatworkUser(null)->notify(ChatworkMessage::make()->body('Hi')->toRoom(555));

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/555/messages');
});

it('throws ChatworkRoutingException when no route is provided', function () {
    fakeChatworkOk();

    $caught = null;
    try {
        makeChatworkUser(null)->notify(new ChatworkMessage('Hi'));
    } catch (ChatworkRoutingException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRoutingException::class);
});

it('throws ChatworkRequestException on 4xx responses', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(['errors' => ['nope']], 400),
    ]);

    $caught = null;
    try {
        makeChatworkUser(111)->notify(new ChatworkMessage('Hi'));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(400);
});

it('throws ChatworkRequestException on 5xx responses (queue retry territory)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response('', 503),
    ]);

    $caught = null;
    try {
        makeChatworkUser(111)->notify(new ChatworkMessage('Hi'));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(503);
});

it('routes via a ChatworkNotification subclass implementing toChatwork', function () {
    fakeChatworkOk();

    $notification = new class() extends ChatworkNotification
    {
        public function toChatwork(object $notifiable): ChatworkMessage
        {
            return ChatworkMessage::make()->body('Subclass body')->selfUnread();
        }
    };

    makeChatworkUser(666)->notify($notification);

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms/666/messages'
        && $r['body'] === 'Subclass body'
        && (int) $r['self_unread'] === 1
    );
});
