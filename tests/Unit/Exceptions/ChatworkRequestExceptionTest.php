<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

it('status・method・path・operationId を公開する', function () {
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

it('ボディから Chatwork の errors[] をパースする', function () {
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

it('ボディから OAuth の error / error_description をパースする', function () {
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

it('rateLimit が指定された場合に rateLimit 配列を公開する', function () {
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

it('rateLimit が指定されていない場合に null を返す', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/123/messages',
        operationId: null,
        body: '{}',
    );

    expect($exception->rateLimit())->toBeNull();
});

it('ボディから access_token / refresh_token / client_secret をマスクする', function () {
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

it('追加のセンシティブ JSON キー (token/authorization/code/client_id/password) をマスクする', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/token',
        operationId: 'issueOAuthToken',
        body: '{"token":"tk-leak","authorization":"Bearer bl-leak","code":"cd-leak","client_id":"ci-leak","password":"pw-leak","scope":"all"}',
    );

    $body = $exception->body();

    expect($body)
        ->toContain('"token":"***"')
        ->toContain('"authorization":"***"')
        ->toContain('"code":"***"')
        ->toContain('"client_id":"***"')
        ->toContain('"password":"***"')
        ->toContain('"scope":"all"');

    foreach (['tk-leak', 'bl-leak', 'cd-leak', 'ci-leak', 'pw-leak'] as $secret) {
        expect(str_contains($body, $secret))->toBeFalse();
    }
});

it('ネストしたオブジェクト内のトークンも再帰的にマスクする', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/token',
        operationId: 'issueOAuthToken',
        body: '{"meta":{"inner":{"access_token":"deep-leak"}},"ok":true}',
    );

    $body = $exception->body();

    expect($body)->toContain('"access_token":"***"')
        ->toContain('"ok":true');
    expect(str_contains($body, 'deep-leak'))->toBeFalse();
});

it('form-urlencoded ボディの秘密値をマスクし非秘密パラメータは保持する', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/token',
        operationId: 'issueOAuthToken',
        body: 'grant_type=authorization_code&client_secret=super-secret-form-value&scope=all',
    );

    $body = $exception->body();

    expect($body)
        ->toContain('client_secret=***')
        ->toContain('grant_type=authorization_code')
        ->toContain('scope=all');
    expect(str_contains($body, 'super-secret-form-value'))->toBeFalse();
});

it('JSON 値に含まれる = は form パターンで破壊されない', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/1/messages',
        operationId: 'createRoomMessage',
        body: '{"note":"a=b=c","scope":"all"}',
    );

    expect($exception->body())->toBe('{"note":"a=b=c","scope":"all"}');
});

it('redaction はエラー本文の生パースに影響しない (errors / error は不変)', function () {
    $exception = new ChatworkRequestException(
        status: 400,
        method: 'POST',
        path: '/rooms/1/messages',
        operationId: 'createRoomMessage',
        body: '{"errors":["body is required"],"access_token":"leak-xyz"}',
    );

    expect($exception->errors())->toBe(['body is required'])
        ->and($exception->error())->toBeNull()
        ->and($exception->body())->toContain('"access_token":"***"');
    expect(str_contains($exception->body(), 'leak-xyz'))->toBeFalse();
});

it('fromResponse() で Illuminate Response からインスタンスを構築しレートリミットヘッダーを取り出す', function () {
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
