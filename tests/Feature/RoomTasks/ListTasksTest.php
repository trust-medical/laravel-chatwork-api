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

it('フィルターなしのとき GET /rooms/{room_id}/tasks をクエリなしで送信する', function () {
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

it('フィルター引数をクエリパラメータにシリアライズする', function () {
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

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
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

it('asDto モードで RoomTaskData の配列を返す', function () {
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

it('asCollection モードで RoomTaskData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->tasks()->list(123);
    /** @var Collection<int, RoomTaskData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(RoomTaskData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->tasks()->list(123);
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray();
    expect($result[0]['task_id'])->toBe(101);
});

it('asDto モードで 204 No Content のとき空配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response('', 204),
    ]);

    $result = Chatwork::rooms()->tasks()->list(123);

    expect($result)->toBe([]);
});

it('asResult モードで成功 Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks' => Http::response(
            fixtureJson('tasks/list-room-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->tasks()->list(123);
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
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

it('429 時に rateLimit() を公開する', function () {
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
