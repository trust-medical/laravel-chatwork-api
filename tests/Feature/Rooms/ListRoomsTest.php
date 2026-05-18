<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('/rooms を GET する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->list();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms');
});

it('asDto モードで RoomData の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->list();

    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result[0])->toBeInstanceOf(RoomData::class);
    expect($result[0]->roomId)->toBe(123);
    expect($result[1]->roomId)->toBe(456);
});

it('asCollection モードで RoomData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->list();
    /** @var Collection<int, RoomData> $result */
    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(RoomData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->list();
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray();
    expect($result[0]['room_id'])->toBe(123);
});

it('401 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            ['errors' => ['unauthorized']],
            401,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->list();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(401);
});
