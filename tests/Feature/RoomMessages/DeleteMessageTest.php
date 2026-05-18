<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\DeletedMessage;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('/rooms/{room_id}/messages/{message_id} を DELETE する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/delete-message-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->messages()->deleteMessage(123, '5');

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/messages/5');
});

it('asDto モードで DeletedMessage DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/5' => Http::response(
            fixtureJson('messages/delete-message-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->messages()->deleteMessage(123, '5');

    expect($result)->toBeInstanceOf(DeletedMessage::class);
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
        Chatwork::rooms()->messages()->deleteMessage(123, '9999');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404);
});

it('asResult モードでスローせず Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages/9999' => Http::response(
            ['errors' => ['not found']],
            404,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->messages()->deleteMessage(123, '9999');

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->failed())->toBeTrue();
});
