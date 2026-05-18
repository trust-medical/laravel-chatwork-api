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

it('クエリなしで GET /contacts を送信する', function () {
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

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
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

it('asDto モードで ContactData の配列を返す', function () {
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

it('asCollection モードで ContactData の Collection を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asCollection()->contacts()->list();
    /** @var Collection<int, ContactData> $result */
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->first())->toBeInstanceOf(ContactData::class);
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->contacts()->list();
    /** @var array<int, array<string, mixed>> $result */
    expect($result)->toBeArray()
        ->and($result[0]['account_id'])->toBe(123);
});

it('asResult モードで Collection に展開せず成功の Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->contacts()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('asDto モードで 204 の空ボディを空配列にマップする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response('', 204),
    ]);

    $result = Chatwork::contacts()->list();

    expect($result)->toBe([]);
});

it('asCollection モードで 204 の空ボディを空の Collection にマップする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response('', 204),
    ]);

    $result = Chatwork::asCollection()->contacts()->list();
    /** @var Collection<int, ContactData> $result */
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(0);
});

it('asResult モードでステータス 204 の成功 Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response('', 204),
    ]);

    $result = Chatwork::asResult()->contacts()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->succeeded())->toBeTrue()
        ->and($result->status())->toBe(204);
});

it('asResult モードで 400 時に例外をスローしない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/contacts' => Http::response(
            fixtureJson('contacts/list-contacts-400.json'),
            400,
        ),
    ]);

    $result = Chatwork::asResult()->contacts()->list();
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['Invalid request']);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
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

it('429 時に rateLimit() を公開する', function () {
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
