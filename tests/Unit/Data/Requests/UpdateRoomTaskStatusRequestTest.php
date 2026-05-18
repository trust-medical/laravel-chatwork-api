<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomTaskStatusRequest;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

it('toArray は body にステータスのバッキング値を入れる', function (TaskStatus $status, string $expected) {
    expect((new UpdateRoomTaskStatusRequest($status))->toArray())->toBe(['body' => $expected]);
})->with([
    'open' => [TaskStatus::Open, 'open'],
    'done' => [TaskStatus::Done, 'done'],
]);

it('status を公開プロパティとして保持する', function () {
    expect((new UpdateRoomTaskStatusRequest(TaskStatus::Done))->status)->toBe(TaskStatus::Done);
});
