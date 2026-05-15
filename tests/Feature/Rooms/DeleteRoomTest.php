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

it('DELETEs /rooms/{room_id} with action_type=delete in a form body', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/delete-room-204.json'),
            204,
        ),
    ]);

    Chatwork::rooms()->deleteRoom(123);

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'DELETE'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['action_type'] === 'delete';
    });
});

it('returns NoContentData in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            fixtureJson('rooms/delete-room-204.json'),
            204,
        ),
    ]);

    $result = Chatwork::rooms()->deleteRoom(123);

    expect($result)->toBeInstanceOf(NoContentData::class);
});

it('throws ChatworkRequestException on 403 when caller lacks permission', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123' => Http::response(
            ['errors' => ['forbidden']],
            403,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->deleteRoom(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(403);
});
