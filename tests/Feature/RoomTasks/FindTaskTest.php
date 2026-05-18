<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('GETs /rooms/{room_id}/tasks/{task_id}', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99' => Http::response(
            fixtureJson('tasks/get-room-task-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->tasks()->find(123, 99);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/tasks/99'
        && $r->data() === []);
});

it('returns a RoomTaskData DTO with nested SimpleAccount and enums', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99' => Http::response(
            fixtureJson('tasks/get-room-task-200.json'),
            200,
        ),
    ]);

    $task = Chatwork::rooms()->tasks()->find(123, 99);

    expect($task)->toBeInstanceOf(RoomTaskData::class)
        ->and($task->taskId)->toBe(99)
        ->and($task->account)->toBeInstanceOf(SimpleAccount::class)
        ->and($task->account->accountId)->toBe(123)
        ->and($task->assignedByAccount)->toBeInstanceOf(SimpleAccount::class)
        ->and($task->assignedByAccount->accountId)->toBe(456)
        ->and($task->messageId)->toBe('13')
        ->and($task->body)->toBe('Buy milk')
        ->and($task->limitTime)->toBe(1735707600)
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->limitType)->toBe(LimitType::Time);
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99' => Http::response(
            fixtureJson('tasks/get-room-task-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->find(123, 99);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('throws ChatworkRequestException on 404', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/tasks/99' => Http::response(
            fixtureJson('tasks/get-room-task-404.json'),
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->tasks()->find(123, 99);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404)
        ->and($caught?->errors())->toBe(['task not found']);
});
