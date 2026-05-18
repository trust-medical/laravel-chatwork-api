<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);

    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'bearer-sales-token',
    ]);

    Http::fake([
        'https://api.chatwork.com/v2/rooms/*/messages' => Http::response(
            ['message_id' => '5'],
            201,
        ),
    ]);
});

it('/rooms/{room_id}/messages に POST する (P2-T01)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
    );
});

it('application/x-www-form-urlencoded を送信する (P2-T02)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(function (Request $r) {
        $contentType = $r->header('Content-Type')[0] ?? '';

        return str_contains($contentType, 'application/x-www-form-urlencoded');
    });
});

it('api_token 接続では x-chatworktoken を送信する (P2-T03)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('bearer 接続では Authorization Bearer を送信する (P2-T04)', function () {
    Chatwork::connection('sales')->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer bearer-sales-token')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('ペイロードに body を含める (P2-T05)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello, Chatwork!');

    Http::assertSent(fn (Request $r) => $r['body'] === 'Hello, Chatwork!');
});

it('selfUnread が true のとき self_unread=1 を送信する (P2-T06)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello', selfUnread: true);

    Http::assertSent(fn (Request $r) => (int) $r['self_unread'] === 1);
});

it('指定しない場合は self_unread を省略する (P2-T07)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(function (Request $r) {
        return ! array_key_exists('self_unread', $r->data());
    });
});

it('空の body では HTTP を送信せず ChatworkValidationException をスローする (P2-T08)', function () {
    try {
        Chatwork::connection()->rooms()->messages()->create(123, '');
    } catch (ChatworkValidationException) {
        Http::assertNothingSent();

        return;
    }

    throw new RuntimeException('ChatworkValidationException を期待');
});

it('65535 文字超の body では HTTP を送信せず ChatworkValidationException をスローする (P2-T09)', function () {
    $body = str_repeat('a', 65536);

    try {
        Chatwork::connection()->rooms()->messages()->create(123, $body);
    } catch (ChatworkValidationException) {
        Http::assertNothingSent();

        return;
    }

    throw new RuntimeException('ChatworkValidationException を期待');
});
