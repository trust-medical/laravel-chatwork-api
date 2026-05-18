<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedRoom;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームエンコードボディで /rooms に POST する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/create-room-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->create(new CreateRoomRequest(
        name: 'New Team',
        membersAdminIds: [1, 2],
        iconPreset: IconPreset::Group,
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'POST'
            && $r->url() === 'https://api.chatwork.com/v2/rooms'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['name'] === 'New Team'
            && $r['members_admin_ids'] === '1,2'
            && $r['icon_preset'] === 'group';
    });
});

it('asDto モードで CreatedRoom DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/create-room-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::rooms()->create(new CreateRoomRequest(
        name: 'New Team',
        membersAdminIds: [1],
    ));

    expect($result)->toBeInstanceOf(CreatedRoom::class);
    expect($result->roomId)->toBe(1234);
});

it('400 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response(
            fixtureJson('rooms/create-room-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->create(new CreateRoomRequest(
            name: 'New Team',
            membersAdminIds: [1],
        ));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400);
});
