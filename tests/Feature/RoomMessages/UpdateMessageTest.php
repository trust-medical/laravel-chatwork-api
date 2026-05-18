<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedMessage;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームボディとともに /rooms/{room_id}/messages/{message_id} を PUT する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/update-message-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->update(123, '5', 'updated text');

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'PUT'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/5'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['body'] === 'updated text';
    });
});

it('body が空のとき HTTP を呼ばず ChatworkValidationException をスローする', function () {
    Http::fake();

    $caught = null;
    try {
        Chatwork::rooms()->messages()->update(123, '5', '');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('asDto モードで UpdatedMessage DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/update-message-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->update(123, '5', 'updated text');

    expect($result)->toBeInstanceOf(UpdatedMessage::class);
    expect($result->messageId)->toBe('5');
});

it('404 で ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/9999' => Http::response(
            ['errors' => ['not found']],
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->messages()->update(123, '9999', 'x');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404);
});
