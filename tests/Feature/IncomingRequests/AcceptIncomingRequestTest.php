<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('ボディなしで PUT /incoming_requests/{request_id} を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-200.json'),
            200,
        ),
    ]);

    Chatwork::incomingRequests()->accept(123);

    Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
        && $r->url() === 'https://api.chatwork.com/v2/incoming_requests/123'
        && $r->data() === []);
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-200.json'),
            200,
        ),
    ]);

    Chatwork::incomingRequests()->accept(123);

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで ContactData の DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-200.json'),
            200,
        ),
    ]);

    $contact = Chatwork::incomingRequests()->accept(123);

    expect($contact)->toBeInstanceOf(ContactData::class)
        ->and($contact->accountId)->toBe(363)
        ->and($contact->roomId)->toBe(1234)
        ->and($contact->name)->toBe('Bob Bob')
        ->and($contact->chatworkId)->toBe('bobbob')
        ->and($contact->organizationId)->toBe(101)
        ->and($contact->organizationName)->toBe('Example Corp')
        ->and($contact->department)->toBe('Sales')
        ->and($contact->avatarImageUrl)->toBe('https://example.com/avatar/363.png');
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->incomingRequests()->accept(123);

    expect($result)->toBeArray()
        ->and($result['account_id'])->toBe(363);
});

it('asResult モードで成功の Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->accept(123);

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::incomingRequests()->accept(123);
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests/123' => Http::response(
            fixtureJson('incoming-requests/accept-incoming-request-429.json'),
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
        Chatwork::incomingRequests()->accept(123);
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
