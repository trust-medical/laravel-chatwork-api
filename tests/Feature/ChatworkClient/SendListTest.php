<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'send-list-token',
    ]);

    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/list-rooms-200.json'),
            200,
        ),
    ]);
});

it('Dto モードでは Collection をアンラップして配列を返す', function () {
    $result = Chatwork::client()->sendList('GET', '/rooms', [], RoomData::class, 'listRooms');

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(RoomData::class);

    Http::assertSentCount(1);
});

it('Collection モードでは Collection をそのまま透過する', function () {
    $result = Chatwork::asCollection()->client()
        ->sendList('GET', '/rooms', [], RoomData::class, 'listRooms');

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(RoomData::class);
});

it('Array モードでは生配列をそのまま透過する', function () {
    $result = Chatwork::asArray()->client()
        ->sendList('GET', '/rooms', [], RoomData::class, 'listRooms');

    expect($result)->toBeArray()
        ->and($result[0]['room_id'])->toBe(123);
});

it('非 Dto モードでは内部でモードを切り替えない', function () {
    $client = Chatwork::asCollection()->client();

    expect($client->mode())->toBe(ResponseMode::Collection);

    $client->sendList('GET', '/rooms', [], RoomData::class, 'listRooms');

    expect($client->mode())->toBe(ResponseMode::Collection);
});
