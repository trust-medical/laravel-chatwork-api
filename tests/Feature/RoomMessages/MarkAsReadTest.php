<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkReadResult;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('/rooms/{room_id}/messages/read を PUT する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/read' => Http::response(
            fixtureJson('messages/mark-read-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->markAsRead(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/read');
});

it('message_id を指定しない場合はフォームボディから省略する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/read' => Http::response(
            fixtureJson('messages/mark-read-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->markAsRead(123);

    Http::assertSent(fn (Request $r) => ! isset($r->data()['message_id']));
});

it('message_id を指定した場合はフォームボディに含めて送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/read' => Http::response(
            fixtureJson('messages/mark-read-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->markAsRead(123, '42');

    Http::assertSent(fn (Request $r) => ($r->data()['message_id'] ?? null) === '42');
});

it('asDto モードで MarkReadResult DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/read' => Http::response(
            fixtureJson('messages/mark-read-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->markAsRead(123);

    expect($result)->toBeInstanceOf(MarkReadResult::class);
    expect($result->unreadNum)->toBe(0);
    expect($result->mentionNum)->toBe(0);
});

it('400 で ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/read' => Http::response(
            ['errors' => ['invalid']],
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->messages()->markAsRead(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400);
});
