<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('/rooms/{room_id} を GET する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/get-room-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->find(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123');
});

it('asDto モードで description を含む RoomData を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/get-room-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->find(123);

    expect($result)->toBeInstanceOf(RoomData::class);
    expect($result->roomId)->toBe(123);
    expect($result->description)->toBe('Group description text');
});

it('404 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/9999' => Http::response(
            fixtureJson('rooms/get-room-404.json'),
            404,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->find(9999);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(404);
});

it('asResult モードでスローせず Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/9999' => Http::response(
            fixtureJson('rooms/get-room-404.json'),
            404,
        ),
    ]);

    $result = Chatwork::asResult()->rooms()->find(9999);

    expect($result)->toBeInstanceOf(Result::class);
    expect($result->failed())->toBeTrue();
});
