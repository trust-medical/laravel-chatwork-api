<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleRoom;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('readonly クラスである', function () {
    expect(MyTaskData::class)->toBeReadonly();
});

it('fromArray でネストした SimpleRoom と SimpleAccount を含む MyTaskData をハイドレートする', function () {
    $data = fixtureJson('my/list-my-tasks-200.json');

    $task = MyTaskData::fromArray($data[0]);

    expect($task->taskId)->toBe(3);
    expect($task->room)->toBeInstanceOf(SimpleRoom::class);
    expect($task->room->roomId)->toBe(322);
    expect($task->room->name)->toBe('Group Chat Name');
    expect($task->room->iconPath)->toBe('https://example.com/icon/group.png');
    expect($task->assignedByAccount)->toBeInstanceOf(SimpleAccount::class);
    expect($task->assignedByAccount->accountId)->toBe(456);
    expect($task->messageId)->toBe('13');
    expect($task->body)->toBe('Buy milk');
    expect($task->limitTime)->toBe(1735707600);
    expect($task->status)->toBe(TaskStatus::Open);
    expect($task->limitType)->toBe(LimitType::Time);
});

it('limit_type が欠けている場合は limitType が null を返す', function () {
    $data = fixtureJson('my/list-my-tasks-200.json');

    $task = MyTaskData::fromArray($data[1]);

    expect($task->limitType)->toBeNull();
    expect($task->status)->toBe(TaskStatus::Done);
});

it('未知のステータスでも例外を投げずに TaskStatus::Open にフォールバックする', function () {
    $task = MyTaskData::fromArray([
        'task_id' => 1,
        'status' => 'archived',
    ]);

    expect($task->status)->toBe(TaskStatus::Open);
    expect($task->room)->toBeInstanceOf(SimpleRoom::class);
    expect($task->assignedByAccount)->toBeInstanceOf(SimpleAccount::class);
});

it('数値の task_id を int にキャストする', function () {
    $task = MyTaskData::fromArray([
        'task_id' => '42',
        'status' => 'open',
    ]);

    expect($task->taskId)->toBe(42);
});
