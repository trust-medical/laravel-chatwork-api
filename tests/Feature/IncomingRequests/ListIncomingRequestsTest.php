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

it('GETs /incoming_requests without query', function () {
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

it('sends x-chatworktoken header for api_token connection', function () {
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

it('returns array of IncomingRequestData in asDto mode', function () {
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

it('returns Collection of IncomingRequestData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->incomingRequests()->list();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(IncomingRequestData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->incomingRequests()->list();

    expect($result)->toBeArray()
        ->and($result[0]['request_id'])->toBe(123);
});

it('returns a successful Result in asResult mode without unwrapping to a Collection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('maps a 204 empty body to an empty array in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::incomingRequests()->list();

    expect($result)->toBe([]);
});

it('maps a 204 empty body to an empty Collection in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->incomingRequests()->list();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(0);
});

it('maps a 204 empty body to an empty array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asArray()->incomingRequests()->list();

    expect($result)->toBe([]);
});

it('returns a successful Result with status 204 in asResult mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response('', 204),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->status())->toBe(204);
});

it('does not throw on 400 in asResult mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/incoming_requests' => Http::response(
            fixtureJson('incoming-requests/list-incoming-requests-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->incomingRequests()->list();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['Invalid request']);
});

it('throws ChatworkRequestException with errors() on 400', function () {
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

it('exposes rateLimit() on 429', function () {
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
