<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response as IlluminateResponse;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'tok',
    ]);
});

it('asDto は 201 で CreatedMessage DTO を返す (P2-T10)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    $result = Chatwork::asDto()->rooms()->messages()->create(123, 'Hello');

    expect($result)->toBeInstanceOf(CreatedMessage::class)
        ->and($result->messageId)->toBe('1024');
});

it('asDto は 400 で errors() を持つ ChatworkRequestException をスローする (P2-T11)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-400.json'),
            400,
        ),
    ]);

    $exception = null;

    try {
        Chatwork::asDto()->rooms()->messages()->create(123, 'Hello');
    } catch (ChatworkRequestException $e) {
        $exception = $e;
    }

    expect($exception)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($exception?->status())->toBe(400)
        ->and($exception?->errors())->toBe(['body is required']);
});

it('asDto は 429 で rateLimit 配列を返す (P2-T12)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-429.json'),
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $exception = null;

    try {
        Chatwork::asDto()->rooms()->messages()->create(123, 'Hello');
    } catch (ChatworkRequestException $e) {
        $exception = $e;
    }

    expect($exception?->status())->toBe(429)
        ->and($exception?->rateLimit())->toBe([
            'limit' => 200,
            'remaining' => 0,
            'reset' => 1735718400,
        ]);
});

it('asResponse は 400 でもスローせず Illuminate Response を返す (P2-T13)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-400.json'),
            400,
        ),
    ]);

    $response = Chatwork::asResponse()->rooms()->messages()->create(123, 'Hello');

    expect($response)->toBeInstanceOf(IlluminateResponse::class)
        ->and($response->status())->toBe(400);
});

it('asResult は 4xx でスローせず失敗した Result を返す (P2-T14)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->messages()->create(123, 'Hello');

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['body is required']);
});

it('asArray は 201 でデコードした JSON 配列を返す (P2-T15)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->messages()->create(123, 'Hello');

    expect($result)->toBe(['message_id' => '1024']);
});

it('asPsrResponse は 201 で PSR-7 ResponseInterface を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    $result = Chatwork::asPsrResponse()->rooms()->messages()->create(123, 'Hello');

    expect($result)->toBeInstanceOf(ResponseInterface::class)
        ->and($result->getStatusCode())->toBe(201);
});
