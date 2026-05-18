<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkUnreadResult;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('message_id をフォームボディに含めて /rooms/{room_id}/messages/unread を PUT する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/unread' => Http::response(
            fixtureJson('messages/mark-unread-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->markAsUnread(123, '42');

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'PUT'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/unread'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['message_id'] === '42';
    });
});

it('message_id が空のとき HTTP を呼ばず ChatworkValidationException をスローする', function () {
    Http::fake();

    $caught = null;
    try {
        Chatwork::rooms()->messages()->markAsUnread(123, '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('asDto モードで MarkUnreadResult DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/unread' => Http::response(
            fixtureJson('messages/mark-unread-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->markAsUnread(123, '42');

    expect($result)->toBeInstanceOf(MarkUnreadResult::class);
    expect($result->unreadNum)->toBe(5);
    expect($result->mentionNum)->toBe(1);
});

it('404 で ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/unread' => Http::response(
            ['errors' => ['not found']],
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->messages()->markAsUnread(123, '42');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404);
});
