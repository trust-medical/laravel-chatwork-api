<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedRoom;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームエンコードボディで /rooms/{room_id} を PUT する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/update-room-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->update(123, new UpdateRoomRequest(
        name: 'Renamed',
        iconPreset: IconPreset::Event,
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'PUT'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['name'] === 'Renamed'
            && $r['icon_preset'] === 'event';
    });
});

it('asDto モードで UpdatedRoom DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/update-room-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->update(123, new UpdateRoomRequest(name: 'Renamed'));

    expect($result)->toBeInstanceOf(UpdatedRoom::class);
    expect($result->roomId)->toBe(123);
});

it('400 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            ['errors' => ['invalid']],
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->update(123, new UpdateRoomRequest(name: 'Renamed'));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400);
});
