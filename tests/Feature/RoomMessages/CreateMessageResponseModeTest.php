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

it('asDto returns CreatedMessage DTO on 201 (P2-T10)', function () {
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

it('asDto throws ChatworkRequestException with errors() on 400 (P2-T11)', function () {
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

it('asDto returns rateLimit array on 429 (P2-T12)', function () {
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

it('asResponse does not throw on 400 and returns an Illuminate Response (P2-T13)', function () {
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

it('asResult returns a failed Result on 4xx without throwing (P2-T14)', function () {
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

it('asArray returns the decoded JSON array on 201 (P2-T15)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->messages()->create(123, 'Hello');

    expect($result)->toBe(['message_id' => '1024']);
});

it('asPsrResponse returns a PSR-7 ResponseInterface on 201', function () {
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
