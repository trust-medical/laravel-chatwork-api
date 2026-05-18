<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomFileData;
use TrustMedical\LaravelChatworkApi\Data\Responses\SimpleAccount;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('accountId 未指定時はクエリなしで GET /rooms/{room_id}/files を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->list(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/files'
        && $r->data() === []);
});

it('accountId をクエリパラメータとしてシリアライズする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files*' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->list(123, accountId: 456);

    Http::assertSent(fn (Request $r) => $r['account_id'] === 456);
});

it('api_token 接続時に x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->files()->list(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで RoomFileData の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->files()->list(123);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(RoomFileData::class);
    expect($result[0]->fileId)->toBe(101);
    expect($result[0]->account)->toBeInstanceOf(SimpleAccount::class);
    expect($result[0]->downloadUrl)->toBeNull();
});

it('asCollection モードで RoomFileData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->files()->list(123);
    /** @var Collection<int, RoomFileData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(RoomFileData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->files()->list(123);
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray();
    expect($result[0]['file_id'])->toBe(101);
});

it('204 No Content 時に asDto モードで空配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response('', 204),
    ]);

    $result = Chatwork::rooms()->files()->list(123);

    expect($result)->toBe([]);
});

it('204 No Content 時に asCollection モードで空の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->rooms()->files()->list(123);
    /** @var Collection<int, RoomFileData> $result */
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->isEmpty())->toBeTrue();
});

it('asResult モードで成功した Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->files()->list(123);
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->files()->list(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/files' => Http::response(
            fixtureJson('files/list-room-files-429.json'),
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
        Chatwork::rooms()->files()->list(123);
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
