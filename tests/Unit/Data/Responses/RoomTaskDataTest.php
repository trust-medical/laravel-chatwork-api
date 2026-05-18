<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('is a readonly class', function () {
    expect(RoomTaskData::class)->toBeReadonly();
});

it('hydrates RoomTaskData with nested SimpleAccount via fromArray', function () {
    $data = fixtureJson('tasks/get-room-task-200.json');

    $task = RoomTaskData::fromArray($data);

    expect($task->taskId)->toBe(99);
    expect($task->account)->toBeInstanceOf(SimpleAccount::class);
    expect($task->account->accountId)->toBe(123);
    expect($task->assignedByAccount)->toBeInstanceOf(SimpleAccount::class);
    expect($task->assignedByAccount->accountId)->toBe(456);
    expect($task->messageId)->toBe('13');
    expect($task->body)->toBe('Buy milk');
    expect($task->limitTime)->toBe(1735707600);
    expect($task->status)->toBe(TaskStatus::Open);
    expect($task->limitType)->toBe(LimitType::Time);
});

it('returns null limitType when limit_type is absent', function () {
    $task = RoomTaskData::fromArray([
        'task_id' => 5,
        'account' => ['account_id' => 1, 'name' => 'X', 'avatar_image_url' => ''],
        'assigned_by_account' => ['account_id' => 2, 'name' => 'Y', 'avatar_image_url' => ''],
        'message_id' => '7',
        'body' => 'no limit',
        'limit_time' => 0,
        'status' => 'done',
    ]);

    expect($task->limitType)->toBeNull();
    expect($task->status)->toBe(TaskStatus::Done);
});

it('falls back to TaskStatus::Open for an unknown status without throwing', function () {
    $task = RoomTaskData::fromArray([
        'task_id' => 1,
        'status' => 'archived',
    ]);

    expect($task->status)->toBe(TaskStatus::Open);
});

it('casts numeric task_id to int', function () {
    $task = RoomTaskData::fromArray([
        'task_id' => '42',
        'status' => 'open',
    ]);

    expect($task->taskId)->toBe(42);
});
