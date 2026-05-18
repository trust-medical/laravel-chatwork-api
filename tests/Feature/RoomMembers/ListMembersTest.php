<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\RoomRole;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('クエリなしで GET /rooms/{room_id}/members を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->list(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/members'
        && $r->data() === []);
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->members()->list(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで RoomMemberData の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->members()->list(123);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(3);
    expect($result[0])->toBeInstanceOf(RoomMemberData::class);
    expect($result[0]->accountId)->toBe(123);
    expect($result[0]->role)->toBe(RoomRole::Admin);
    expect($result[1]->role)->toBe(RoomRole::Member);
    expect($result[2]->role)->toBe(RoomRole::Readonly);
});

it('asCollection モードで RoomMemberData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->members()->list(123);
    /** @var Collection<int, RoomMemberData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(3);
    expect($result->first())->toBeInstanceOf(RoomMemberData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->members()->list(123);
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray();
    expect($result[0]['account_id'])->toBe(123);
});

it('asResult モードで Collection に展開せず成功 Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->members()->list(123);
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('asResult モードで 400 時に例外をスローしない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->members()->list(123);
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['room_id is invalid']);
});

it('400 時に errors() を持つ ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->members()->list(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-429.json'),
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
        Chatwork::rooms()->members()->list(123);
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
