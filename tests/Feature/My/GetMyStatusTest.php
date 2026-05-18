<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('クエリなしで GET /my/status を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-200.json'),
            200,
        ),
    ]);

    Chatwork::my()->status();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/my/status'
        && $r->data() === []);
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-200.json'),
            200,
        ),
    ]);

    Chatwork::my()->status();

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで MyStatusData の DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-200.json'),
            200,
        ),
    ]);

    $status = Chatwork::my()->status();

    expect($status)->toBeInstanceOf(MyStatusData::class)
        ->and($status->unreadRoomNum)->toBe(2)
        ->and($status->mentionRoomNum)->toBe(1)
        ->and($status->mytaskRoomNum)->toBe(3)
        ->and($status->unreadNum)->toBe(12)
        ->and($status->mentionNum)->toBe(4)
        ->and($status->mytaskNum)->toBe(7);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->my()->status();

    expect($result)->toBeArray()
        ->and($result['unread_num'])->toBe(12);
});

it('asResult モードで成功の Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->my()->status();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::my()->status();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/my/status' => Http::response(
            fixtureJson('my/get-my-status-429.json'),
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
        Chatwork::my()->status();
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
