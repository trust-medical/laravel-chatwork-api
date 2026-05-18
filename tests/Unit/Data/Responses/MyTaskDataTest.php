<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleRoom;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('is a readonly class', function () {
    expect(MyTaskData::class)->toBeReadonly();
});

it('hydrates MyTaskData with nested SimpleRoom and SimpleAccount via fromArray', function () {
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

it('returns null limitType when limit_type is absent', function () {
    $data = fixtureJson('my/list-my-tasks-200.json');

    $task = MyTaskData::fromArray($data[1]);

    expect($task->limitType)->toBeNull();
    expect($task->status)->toBe(TaskStatus::Done);
});

it('falls back to TaskStatus::Open for an unknown status without throwing', function () {
    $task = MyTaskData::fromArray([
        'task_id' => 1,
        'status' => 'archived',
    ]);

    expect($task->status)->toBe(TaskStatus::Open);
    expect($task->room)->toBeInstanceOf(SimpleRoom::class);
    expect($task->assignedByAccount)->toBeInstanceOf(SimpleAccount::class);
});

it('casts numeric task_id to int', function () {
    $task = MyTaskData::fromArray([
        'task_id' => '42',
        'status' => 'open',
    ]);

    expect($task->taskId)->toBe(42);
});
