<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomMemberData;
use TrustMedical\LaravelChatworkApi\Enums\RoomRole;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('GETs /rooms/{room_id}/members without query', function () {
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

it('sends x-chatworktoken header for api_token connection', function () {
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

it('returns array of RoomMemberData in asDto mode', function () {
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

it('returns Collection of RoomMemberData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->members()->list(123);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(3);
    expect($result->first())->toBeInstanceOf(RoomMemberData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/members' => Http::response(
            fixtureJson('members/list-room-members-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->members()->list(123);

    expect($result)->toBeArray();
    expect($result[0]['account_id'])->toBe(123);
});

it('throws ChatworkRequestException with errors() on 400', function () {
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

it('exposes rateLimit() on 429', function () {
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
