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

it('PUTs /incoming_requests/{request_id} with no body', function () {
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

it('sends x-chatworktoken header for api_token connection', function () {
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

it('returns a ContactData DTO in asDto mode', function () {
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

it('returns raw array in asArray mode', function () {
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

it('returns a successful Result in asResult mode', function () {
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

it('throws ChatworkRequestException with errors() on 400', function () {
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

it('exposes rateLimit() on 429', function () {
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
