<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームエンコードボディで PUT /rooms/{room_id}/tasks/{task_id}/status を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'PUT'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/tasks/99/status'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['body'] === 'done';
    });
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで更新後の RoomTaskData DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-200.json'),
            200,
        ),
    ]);

    $task = Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);

    expect($task)->toBeInstanceOf(RoomTaskData::class)
        ->and($task->taskId)->toBe(99)
        ->and($task->status)->toBe(TaskStatus::Done);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['status is invalid']);
});

it('404 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-404.json'),
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404)
        ->and($caught?->errors())->toBe(['task not found']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99/status' => Http::response(
            fixtureJson('tasks/update-room-task-status-429.json'),
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
        Chatwork::rooms()->tasks()->updateStatus(123, 99, TaskStatus::Done);
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
