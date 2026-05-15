<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

it('exposes status, method, path, operationId', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/123/messages',
        operationId: 'createRoomMessage',
        body: '{"errors":["body is required"]}',
    );

    expect($exception->status())->toBe(400)
        ->and($exception->method())->toBe('POST')
        ->and($exception->path())->toBe('/rooms/123/messages')
        ->and($exception->operationId())->toBe('createRoomMessage');
});

it('parses Chatwork errors[] from body', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/123/messages',
        operationId: 'createRoomMessage',
        body: '{"errors":["body is required","title too long"]}',
    );

    expect($exception->errors())->toBe(['body is required', 'title too long'])
        ->and($exception->error())->toBeNull()
        ->and($exception->errorDescription())->toBeNull();
});

it('parses OAuth error / error_description from body', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/token',
        operationId: 'issueOAuthToken',
        body: '{"error":"invalid_grant","error_description":"The authorization code has expired."}',
    );

    expect($exception->error())->toBe('invalid_grant')
        ->and($exception->errorDescription())->toBe('The authorization code has expired.')
        ->and($exception->errors())->toBe([]);
});

it('exposes rateLimit array when provided', function () {
    $exception = new ChatworkRequestException(
        status: 429,
        method: 'POST',
        path: '/rooms/123/messages',
        operationId: 'createRoomMessage',
        body: '{"errors":["rate limit exceeded"]}',
        rateLimit: ['limit' => 200, 'remaining' => 0, 'reset' => 1735718400],
    );

    expect($exception->rateLimit())->toBe([
        'limit' => 200,
        'remaining' => 0,
        'reset' => 1735718400,
    ]);
});

it('returns null rateLimit when not provided', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/123/messages',
        operationId: null,
        body: '{}',
    );

    expect($exception->rateLimit())->toBeNull();
});

it('redacts access_token / refresh_token / client_secret from body', function () {
    $exception = new ChatworkRequestException(
        status: 200,
        method: 'POST',
        path: '/token',
        operationId: 'issueOAuthToken',
        body: '{"access_token":"sk-secret-abc","refresh_token":"rt-secret-def","client_secret":"cs-secret-ghi","scope":"all"}',
    );

    $body = $exception->body();

    expect($body)
        ->toContain('"access_token":"***"')
        ->toContain('"refresh_token":"***"')
        ->toContain('"client_secret":"***"')
        ->toContain('"scope":"all"');

    expect(str_contains($body, 'sk-secret'))->toBeFalse();
    expect(str_contains($body, 'rt-secret'))->toBeFalse();
    expect(str_contains($body, 'cs-secret'))->toBeFalse();
});

it('builds from an Illuminate Response and extracts rate-limit headers', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            ['errors' => ['rate limit exceeded']],
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $response = Http::post('https://api.chatwork.com/v2/rooms/123/messages');

    $exception = ChatworkRequestException::fromResponse(
        $response,
        'POST',
        '/rooms/123/messages',
        'createRoomMessage',
    );

    expect($exception->status())->toBe(429)
        ->and($exception->method())->toBe('POST')
        ->and($exception->path())->toBe('/rooms/123/messages')
        ->and($exception->operationId())->toBe('createRoomMessage')
        ->and($exception->errors())->toBe(['rate limit exceeded'])
        ->and($exception->rateLimit())->toBe([
            'limit' => 200,
            'remaining' => 0,
            'reset' => 1735718400,
        ]);
});
