<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('/rooms/{room_id}/messages/{message_id} を GET する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/get-message-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->find(123, '5');

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/5');
});

it('asDto モードで MessageData を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/get-message-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->find(123, '5');

    expect($result)->toBeInstanceOf(MessageData::class);
    expect($result->messageId)->toBe('5');
    expect($result->account->name)->toBe('Bob');
});

it('404 で ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/9999' => Http::response(
            ['errors' => ['message not found']],
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->messages()->find(123, '9999');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404);
});

it('asResult モードで失敗ステータスの Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/9999' => Http::response(
            ['errors' => ['message not found']],
            404,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->messages()->find(123, '9999');

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->failed())->toBeTrue();
});
