<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\IncomingRequestData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('クエリなしで GET /incoming_requests を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    Chatwork::incomingRequests()->list();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/incoming_requests'
        && $r->data() === []);
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    Chatwork::incomingRequests()->list();

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで IncomingRequestData の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::incomingRequests()->list();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(IncomingRequestData::class)
        ->and($result[0]->requestId)->toBe(123)
        ->and($result[0]->accountId)->toBe(363)
        ->and($result[0]->message)->toBe('Please add me to your contacts.')
        ->and($result[0]->name)->toBe('Bob Bob')
        ->and($result[0]->chatworkId)->toBe('bobbob')
        ->and($result[0]->organizationId)->toBe(101)
        ->and($result[0]->organizationName)->toBe('Example Corp')
        ->and($result[0]->department)->toBe('Sales')
        ->and($result[0]->avatarImageUrl)->toBe('https://example.com/avatar/363.png')
        ->and($result[1]->requestId)->toBe(124)
        ->and($result[1]->accountId)->toBe(364);
});

it('asCollection モードで IncomingRequestData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->incomingRequests()->list();
    /** @var Collection<int, IncomingRequestData> $result */
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(IncomingRequestData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->incomingRequests()->list();
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray()
        ->and($result[0]['request_id'])->toBe(123);
});

it('asResult モードで Collection に展開せず成功の Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('asDto モードで 204 の空ボディを空配列にマップする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::incomingRequests()->list();

    expect($result)->toBe([]);
});

it('asCollection モードで 204 の空ボディを空の Collection にマップする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->incomingRequests()->list();
    /** @var Collection<int, IncomingRequestData> $result */
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(0);
});

it('asArray モードで 204 の空ボディを空配列にマップする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asArray()->incomingRequests()->list();
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBe([]);
});

it('asResult モードでステータス 204 の成功 Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->status())->toBe(204);
});

it('asResult モードで 400 時に例外をスローしない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['Invalid request']);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::incomingRequests()->list();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-429.json'),
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
        Chatwork::incomingRequests()->list();
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
