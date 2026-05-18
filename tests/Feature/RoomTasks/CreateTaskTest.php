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

it('POSTs /rooms/{room_id}/tasks with form-encoded body', function () {
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

it('omits optional fields when not provided', function () {
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

it('serializes limit and limit_type when provided', function () {
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

it('sends x-chatworktoken header for api_token connection', function () {
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

it('returns CreatedTask DTO in asDto mode', function () {
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

it('throws ChatworkValidationException for empty body without sending HTTP', function () {
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

it('throws ChatworkValidationException for empty toIds', function () {
    $caught = null;
    try {
        new CreateRoomTaskRequest(body: 'Buy milk', toIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('throws ChatworkRequestException with errors() on 400', function () {
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

it('exposes rateLimit() on 429', function () {
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
