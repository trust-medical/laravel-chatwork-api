<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\NoContentData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('フォームボディに action_type=leave を含めて /rooms/{room_id} を DELETE する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/leave-room-204.json'),
            204,
        ),
    ]);

    Chatwork::rooms()->leaveRoom(123);

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'DELETE'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['action_type'] === 'leave';
    });
});

it('asDto モードで NoContentData を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/leave-room-204.json'),
            204,
        ),
    ]);

    $result = Chatwork::rooms()->leaveRoom(123);

    expect($result)->toBeInstanceOf(NoContentData::class);
});

it('403 時に ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            ['errors' => ['forbidden']],
            403,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->leaveRoom(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(403);
});
