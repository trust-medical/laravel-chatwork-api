<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomLinkData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('DELETEs /rooms/{room_id}/link without a request body', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/delete-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->deleteLink(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
        && $r->url() === 'https://api.chatwork.com/v2/rooms/123/link'
        && $r->body() === '');
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/delete-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->deleteLink(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('returns the resulting RoomLinkData DTO in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/delete-room-link-200.json'),
            200,
        ),
    ]);

    $link = Chatwork::rooms()->links()->deleteLink(123);

    expect($link)->toBeInstanceOf(RoomLinkData::class)
        ->and($link->public)->toBeFalse();
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/delete-room-link-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->links()->deleteLink(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['room_id is invalid']);
});

it('exposes rateLimit() on 429', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/delete-room-link-429.json'),
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
        Chatwork::rooms()->links()->deleteLink(123);
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
