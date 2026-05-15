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

it('POSTs to /rooms/{room_id}/messages (P2-T01)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
    );
});

it('sends application/x-www-form-urlencoded (P2-T02)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(function (Request $r) {
        $contentType = $r->header('Content-Type')[0] ?? '';

        return str_contains($contentType, 'application/x-www-form-urlencoded');
    });
});

it('sends x-chatworktoken for api_token connection (P2-T03)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('sends Authorization Bearer for bearer connection (P2-T04)', function () {
    Chatwork::connection('sales')->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer bearer-sales-token')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('includes body in the payload (P2-T05)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello, Chatwork!');

    Http::assertSent(fn (Request $r) => $r['body'] === 'Hello, Chatwork!');
});

it('sends self_unread=1 when selfUnread is true (P2-T06)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello', selfUnread: true);

    Http::assertSent(fn (Request $r) => (int) $r['self_unread'] === 1);
});

it('omits self_unread when not specified (P2-T07)', function () {
    Chatwork::connection()->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(function (Request $r) {
        return ! array_key_exists('self_unread', $r->data());
    });
});

it('throws ChatworkValidationException for empty body without sending HTTP (P2-T08)', function () {
    try {
        Chatwork::connection()->rooms()->messages()->create(123, '');
    } catch (ChatworkValidationException) {
        Http::assertNothingSent();

        return;
    }

    throw new RuntimeException('Expected ChatworkValidationException');
});

it('throws ChatworkValidationException for body over 65535 chars without sending HTTP (P2-T09)', function () {
    $body = str_repeat('a', 65536);

    try {
        Chatwork::connection()->rooms()->messages()->create(123, $body);
    } catch (ChatworkValidationException) {
        Http::assertNothingSent();

        return;
    }

    throw new RuntimeException('Expected ChatworkValidationException');
});
