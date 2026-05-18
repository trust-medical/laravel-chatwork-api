<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyAccountData;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\Result;

beforeEach(function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'api-default-token',
    ]);
});

it('クエリなしで GET /me を送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-200.json'),
            200,
        ),
    ]);

    Chatwork::me()->get();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && $r->url() === 'https://api.chatwork.com/v2/me'
        && $r->data() === []);
});

it('api_token 接続で x-chatworktoken ヘッダーを送信する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-200.json'),
            200,
        ),
    ]);

    Chatwork::me()->get();

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-default-token')
        && ! $r->hasHeader('Authorization'));
});

it('asDto モードで MyAccountData の DTO を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-200.json'),
            200,
        ),
    ]);

    $me = Chatwork::me()->get();

    expect($me)->toBeInstanceOf(MyAccountData::class)
        ->and($me->accountId)->toBe(123)
        ->and($me->roomId)->toBe(322)
        ->and($me->name)->toBe('John Doe')
        ->and($me->chatworkId)->toBe('tarochatworkid')
        ->and($me->organizationId)->toBe(101)
        ->and($me->organizationName)->toBe('Hello Company')
        ->and($me->department)->toBe('Marketing')
        ->and($me->title)->toBe('CMO')
        ->and($me->url)->toBe('https://example.com')
        ->and($me->introduction)->toBe('Self introduction text')
        ->and($me->mail)->toBe('taro@example.com')
        ->and($me->telOrganization)->toBe('XXX-XXXX-XXXX')
        ->and($me->telExtension)->toBe('YYY-YYYY-YYYY')
        ->and($me->telMobile)->toBe('ZZZ-ZZZZ-ZZZZ')
        ->and($me->skype)->toBe('myskype_id')
        ->and($me->facebook)->toBe('myfacebook_id')
        ->and($me->twitter)->toBe('mytwitter_id')
        ->and($me->avatarImageUrl)->toBe('https://example.com/avatar/123.png')
        ->and($me->loginMail)->toBe('login@example.com');
});

it('asArray モードで生の配列を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asArray()->me()->get();

    expect($result)->toBeArray()
        ->and($result['account_id'])->toBe(123);
});

it('asResult モードで成功の Result を返す', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-200.json'),
            200,
        ),
    ]);

    $result = Chatwork::asResult()->me()->get();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200);
});

it('400 時に errors() 付きで ChatworkRequestException をスローする', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-400.json'),
            400,
        ),
    ]);

    $caught = null;
    try {
        Chatwork::me()->get();
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught?->status())->toBe(400)
        ->and($caught?->errors())->toBe(['Invalid request']);
});

it('429 時に rateLimit() を公開する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/me' => Http::response(
            fixtureJson('me/get-me-429.json'),
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
        Chatwork::me()->get();
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
