<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
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

it('GETs /contacts without query', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    Chatwork::contacts()->list();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/contacts'
        && $r->data() === []);
});

it('sends x-chatworktoken header for api_token connection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    Chatwork::contacts()->list();

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('returns array of ContactData in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::contacts()->list();

    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(ContactData::class)
        ->and($result[0]->accountId)->toBe(123)
        ->and($result[0]->roomId)->toBe(322)
        ->and($result[0]->name)->toBe('Alice Contact')
        ->and($result[0]->chatworkId)->toBe('alice')
        ->and($result[0]->organizationId)->toBe(101)
        ->and($result[0]->organizationName)->toBe('Example Corp')
        ->and($result[0]->department)->toBe('Engineering')
        ->and($result[0]->avatarImageUrl)->toBe('https://example.com/avatar/123.png')
        ->and($result[1]->accountId)->toBe(456)
        ->and($result[1]->roomId)->toBe(654);
});

it('returns Collection of ContactData in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->contacts()->list();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(ContactData::class);
});

it('returns raw array in asArray mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->contacts()->list();

    expect($result)->toBeArray()
        ->and($result[0]['account_id'])->toBe(123);
});

it('returns a successful Result in asResult mode without unwrapping to a Collection', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->contacts()->list();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('maps a 204 empty body to an empty array in asDto mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response('', 204),
    ]);

    $result = Chatwork::contacts()->list();

    expect($result)->toBe([]);
});

it('maps a 204 empty body to an empty Collection in asCollection mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->contacts()->list();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(0);
});

it('does not throw on 400 in asResult mode', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->contacts()->list();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['Invalid request']);
});

it('throws ChatworkRequestException with errors() on 400', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::contacts()->list();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('exposes rateLimit() on 429', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-429.json'),
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
        Chatwork::contacts()->list();
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
