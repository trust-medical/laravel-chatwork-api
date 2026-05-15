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

it('GETs /rooms', function () {
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

it('returns array of RoomData in asDto mode', function () {
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

it('returns Collection of RoomData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->rooms()->list();

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result)->toHaveCount(2);
    expect($result->first())->toBeInstanceOf(RoomData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->rooms()->list();

    expect($result)->toBeArray();
    expect($result[0]['room_id'])->toBe(123);
});

it('throws ChatworkRequestException on 401', function () {
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
