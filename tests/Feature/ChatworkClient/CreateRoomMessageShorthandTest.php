<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'short-token',
    ]);

    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(
            ['message_id' => '777'],
            201,
        ),
    ]);
});

it('Chatwork::client()->createRoomMessage delegates to RoomMessagesResource::create', function () {
    $result = Chatwork::client()->createRoomMessage(456, 'shorthand body');

    expect($result)->toBeInstanceOf(CreatedMessage::class)
        ->and($result->messageId)->toBe('777');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/456/messages'
        && $r['body'] === 'shorthand body'
    );
});

it('shorthand passes selfUnread through to the underlying request', function () {
    Chatwork::client()->createRoomMessage(456, 'with self_unread', selfUnread: true);

    Http::assertSent(fn (Request $r) => (int) $r['self_unread'] === 1);
});
