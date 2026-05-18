<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomLinkData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('POSTs /rooms/{room_id}/link with form-encoded body', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->create(123, new RoomLinkRequest(
        code: 'customcode',
        needAcceptance: false,
        description: 'Welcome aboard',
    ));

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'POST'
            && $r->url() === 'https://api.chatwork.com/v2/rooms/123/link'
            && str_contains($ct, 'application/x-www-form-urlencoded')
            && $r['code'] === 'customcode'
            && $r['need_acceptance'] === 0
            && $r['description'] === 'Welcome aboard';
    });
});

it('serializes needAcceptance true as 1', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->create(123, new RoomLinkRequest(needAcceptance: true));

    Http::assertSent(fn (Request $r) => $r['need_acceptance'] === 1);
});

it('omits optional fields when not provided', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->create(123, new RoomLinkRequest());

    Http::assertSent(fn (Request $r) => ! isset($r->data()['code'])
        && ! isset($r->data()['need_acceptance'])
        && ! isset($r->data()['description']));
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-200.json'),
            200,
        ),
    ]);

    Chatwork::rooms()->links()->create(123, new RoomLinkRequest());

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('returns a RoomLinkData DTO in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-200.json'),
            200,
        ),
    ]);

    $link = Chatwork::rooms()->links()->create(123, new RoomLinkRequest(code: 'customcode'));

    expect($link)->toBeInstanceOf(RoomLinkData::class)
        ->and($link->public)->toBeTrue()
        ->and($link->url)->toBe('https://www.chatwork.com/g/customcode')
        ->and($link->needAcceptance)->toBeFalse();
});

it('throws ChatworkValidationException for an empty code without sending HTTP', function () {
    Http::fake();

    $caught = null;
    try {
        Chatwork::rooms()->links()->create(123, new RoomLinkRequest(code: ''));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
    Http::assertNothingSent();
});

it('throws ChatworkValidationException for a code longer than 50 characters', function () {
    $caught = null;
    try {
        new RoomLinkRequest(code: str_repeat('a', 51));
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::rooms()->links()->create(123, new RoomLinkRequest(code: 'taken'));
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['code is already used']);
});

it('exposes rateLimit() on 429', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/link' => Http::response(
            fixtureJson('links/create-room-link-429.json'),
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
        Chatwork::rooms()->links()->create(123, new RoomLinkRequest());
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
