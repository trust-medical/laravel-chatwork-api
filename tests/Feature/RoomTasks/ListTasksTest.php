<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('GETs /rooms/{room_id}/tasks without query when no filters given', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->list(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/tasks'
        && $r->data() === []);
});

it('serializes filter arguments as query parameters', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks*' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->list(
        123,
        accountId: 456,
        assignedByAccountId: 789,
        status: TaskStatus::Open,
    );

    Http::assertSent(fn (Request $r) => $r['account_id'] === 456
        && $r['assigned_by_account_id'] === 789
        && $r['status'] === 'open');
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->list(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('returns array of RoomTaskData in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->tasks()->list(123);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(RoomTaskData::class);
    expect($result[0]->taskId)->toBe(101);
    expect($result[0]->status)->toBe(TaskStatus::Open);
    expect($result[1]->status)->toBe(TaskStatus::Done);
});

it('returns Collection of RoomTaskData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->tasks()->list(123);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(RoomTaskData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->tasks()->list(123);

    expect($result)->toBeArray();
    expect($result[0]['task_id'])->toBe(101);
});

it('returns an empty array in asDto mode on 204 No Content', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response('', 204),
    ]);

    $result = Chatwork::rooms()->tasks()->list(123);

    expect($result)->toBe([]);
});

it('returns a successful Result in asResult mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->tasks()->list(123);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->list(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('exposes rateLimit() on 429', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-429.json'),
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
        Chatwork::rooms()->tasks()->list(123);
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
