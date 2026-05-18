<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('readonly クラスである', function () {
    expect(RoomTaskData::class)->toBeReadonly();
});

it('fromArray で RoomTaskData をネストした SimpleAccount 付きでハイドレートする', function () {
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

it('limit_type が存在しない場合に limitType を null で返す', function () {
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

it('不明な status の場合に例外なく TaskStatus::Open にフォールバックする', function () {
    $task = RoomTaskData::fromArray([
        'task_id' => 1,
        'status' => 'archived',
    ]);

    expect($task->status)->toBe(TaskStatus::Open);
});

it('数値の task_id を int にキャストする', function () {
    $task = RoomTaskData::fromArray([
        'task_id' => '42',
        'status' => 'open',
    ]);

    expect($task->taskId)->toBe(42);
});
