<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleRoom;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
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

it('GETs /my/tasks without query when no filters given', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::my()->tasks();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/my/tasks'
        && $r->data() === []);
});

it('serializes filter arguments as query parameters', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks*' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::my()->tasks(
        assignedByAccountId: 789,
        status: TaskStatus::Open,
    );

    Http::assertSent(fn (Request $r) => $r['assigned_by_account_id'] === 789
        && $r['status'] === 'open');
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    Chatwork::my()->tasks();

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('returns array of MyTaskData in asDto mode with nested DTOs and enums', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::my()->tasks();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(MyTaskData::class)
        ->and($result[0]->taskId)->toBe(3)
        ->and($result[0]->room)->toBeInstanceOf(SimpleRoom::class)
        ->and($result[0]->room->roomId)->toBe(322)
        ->and($result[0]->room->name)->toBe('Group Chat Name')
        ->and($result[0]->room->iconPath)->toBe('https://example.com/icon/group.png')
        ->and($result[0]->assignedByAccount)->toBeInstanceOf(SimpleAccount::class)
        ->and($result[0]->assignedByAccount->accountId)->toBe(456)
        ->and($result[0]->messageId)->toBe('13')
        ->and($result[0]->body)->toBe('Buy milk')
        ->and($result[0]->limitTime)->toBe(1735707600)
        ->and($result[0]->status)->toBe(TaskStatus::Open)
        ->and($result[0]->limitType)->toBe(LimitType::Time)
        ->and($result[1]->status)->toBe(TaskStatus::Done)
        ->and($result[1]->limitType)->toBeNull();
});

it('returns Collection of MyTaskData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->my()->tasks();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(MyTaskData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->my()->tasks();

    expect($result)->toBeArray()
        ->and($result[0]['task_id'])->toBe(3);
});

it('returns a successful Result in asResult mode without unwrapping to a Collection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->my()->tasks();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('maps a 204 empty body to an empty array in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response('', 204),
    ]);

    $result = Chatwork::my()->tasks();

    expect($result)->toBe([]);
});

it('maps a 204 empty body to an empty Collection in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->my()->tasks();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(0);
});

it('returns a successful Result with status 204 in asResult mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response('', 204),
    ]);

    $result = Chatwork::asResult()->my()->tasks();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->status())->toBe(204);
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::my()->tasks();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('exposes rateLimit() on 429', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/tasks' => Http::response(
            fixtureJson('my/list-my-tasks-429.json'),
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
        Chatwork::my()->tasks();
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
