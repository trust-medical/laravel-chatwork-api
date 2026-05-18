<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedTask;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームエンコードボディで POST /rooms/{room_id}/tasks を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1, 2],
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'POST'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/tasks'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['body'] === 'Buy milk'
            && $r['to_ids'] === '1,2';
    });
});

it('省略可能なフィールドが指定されない場合は送信しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1],
    ));

    Http::assertSent(fn (Request $r) => ! isset($r->data()['limit'])
        && ! isset($r->data()['limit_type']));
});

it('limit と limit_type が指定された場合にシリアライズする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1, 2],
        limit: 1735707600,
        limitType: LimitType::Time,
    ));

    Http::assertSent(fn (Request $r) => $r['limit'] === 1735707600
        && $r['limit_type'] === 'time');
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1],
    ));

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで CreatedTask DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
        body: 'Buy milk',
        toIds: [1, 2],
    ));

    expect($result)->toBeInstanceOf(CreatedTask::class)
        ->and($result->taskIds)->toBe([123, 124]);
});

it('body が空の場合は HTTP を送信せず ChatworkValidationException をスローする', function () {
    Http::fake();

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
            body: '',
            toIds: [1],
        ));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('toIds が空の場合に ChatworkValidationException をスローする', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
            body: 'Buy milk',
            toIds: [1],
        ));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['body is required']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/create-room-task-429.json'),
            429,
            [
                'x-ratelimit-limit' => '200',
                'x-ratelimit-remaining' => '0',
                'x-ratelimit-reset' => '1735718400',
            ],
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
            body: 'Buy milk',
            toIds: [1],
        ));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(429)
        ->and($caught?->rateLimit())->toBe([
            'limit' => 200,
            'remaining' => 0,
            'reset' => 1735718400,
        ]);
});
