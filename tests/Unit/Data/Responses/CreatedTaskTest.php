<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedTask;

it('readonly クラスである', function () {
    expect(CreatedTask::class)->toBeReadonly();
});

it('fromArray でタスク ID 一覧をハイドレートする', function () {
    $data = fixtureJson('tasks/create-room-task-200.json');

    $created = CreatedTask::fromArray($data);

    expect($created->taskIds)->toBe([123, 124]);
});

it('数値 ID を int にキャストし再インデックスする', function () {
    $created = CreatedTask::fromArray(['task_ids' => ['7', '8']]);

    expect($created->taskIds)->toBe([7, 8]);
});

it('task_ids が欠けている場合は空配列をデフォルトにする', function () {
    $created = CreatedTask::fromArray([]);

    expect($created->taskIds)->toBe([]);
});
