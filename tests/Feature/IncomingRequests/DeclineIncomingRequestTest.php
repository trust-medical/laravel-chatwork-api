<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\NoContentData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('ボディなしで DELETE /incoming_requests/{request_id} を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response('', 204),
    ]);

    Chatwork::incomingRequests()->decline(123);

    Http::assertSent(function (Request $r) {
        $ct = $r->header('Content-Type')[0] ?? '';

        return $r->method() === 'DELETE'
            && $r->url() === 'https://api.chatwork.com/v2/incoming_requests/123'
            && $r->data() === []
            && ! str_contains($ct, 'application/x-www-form-urlencoded');
    });
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response('', 204),
    ]);

    Chatwork::incomingRequests()->decline(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで NoContentData を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response('', 204),
    ]);

    $result = Chatwork::incomingRequests()->decline(123);

    expect($result)->toBeInstanceOf(NoContentData::class);
});

it('asResult モードでステータス 204 の成功 Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response('', 204),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->decline(123);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->status())->toBe(204);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/decline-incoming-request-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::incomingRequests()->decline(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/decline-incoming-request-429.json'),
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
        Chatwork::incomingRequests()->decline(123);
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
