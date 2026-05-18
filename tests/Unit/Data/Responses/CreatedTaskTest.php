<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedTask;

it('is a readonly class', function () {
    expect(CreatedTask::class)->toBeReadonly();
});

it('hydrates task ids via fromArray', function () {
    $data = fixtureJson('tasks/create-room-task-200.json');

    $created = CreatedTask::fromArray($data);

    expect($created->taskIds)->toBe([123, 124]);
});

it('casts numeric ids to int and reindexes', function () {
    $created = CreatedTask::fromArray(['task_ids' => ['7', '8']]);

    expect($created->taskIds)->toBe([7, 8]);
});

it('defaults to an empty array when task_ids is missing', function () {
    $created = CreatedTask::fromArray([]);

    expect($created->taskIds)->toBe([]);
});
