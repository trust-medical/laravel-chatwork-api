<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.redirect_uri', 'https://app.example.com/callback');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
});

function makeOAuthClient(): OAuthClient
{
    return new OAuthClient(new CacheStateStore(Cache::store()), config('chatwork.oauth'));
}

it('authorization_code grantで/tokenへPOSTする (Confidential = HTTP Basic 認証)', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    makeOAuthClient()->exchange('auth-code-123');

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $expectedAuth = 'Basic ' . base64_encode('client-abc:super-secret');

        return $request->method() === 'POST'
            && $request->url() === 'https://oauth.chatwork.com/token'
            && $request->hasHeader('Authorization', $expectedAuth)
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
            && $body['grant_type'] === 'authorization_code'
            && $body['code'] === 'auth-code-123'
            && $body['redirect_uri'] === 'https://app.example.com/callback'
            && ! array_key_exists('client_id', $body)
            && ! array_key_exists('client_secret', $body);
    });
});

it('Public client (client_secret 未設定) は client_id を body に入れ Authorization ヘッダを送らない', function () {
    Config::set('chatwork.oauth.client_secret', null);

    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    makeOAuthClient()->exchange('auth-code-123');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->method() === 'POST'
            && $request->url() === 'https://oauth.chatwork.com/token'
            && ! $request->hasHeader('Authorization')
            && $body['grant_type'] === 'authorization_code'
            && $body['code'] === 'auth-code-123'
            && $body['redirect_uri'] === 'https://app.example.com/callback'
            && $body['client_id'] === 'client-abc'
            && ! array_key_exists('client_secret', $body);
    });
});

it('Public client (client_secret 空文字) も Public モード扱い', function () {
    Config::set('chatwork.oauth.client_secret', '');

    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    makeOAuthClient()->exchange('auth-code-123');

    Http::assertSent(function (Request $request) {
        return ! $request->hasHeader('Authorization')
            && $request['client_id'] === 'client-abc';
    });
});

it('レスポンスをパースしたTokenSetを返す', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $tokenSet = makeOAuthClient()->exchange('auth-code-123');

    expect($tokenSet)->toBeInstanceOf(TokenSet::class);
    expect($tokenSet->accessToken)->toBe('sample-access-token');
    expect($tokenSet->refreshToken)->toBe('sample-refresh-token');
});

it('400 invalid_grantのときChatworkRequestExceptionをスローする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-400.json'), 400),
    ]);

    $caught = null;
    try {
        makeOAuthClient()->exchange('expired-code');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->status())->toBe(400)
        ->and($caught?->error())->toBe('invalid_grant');
});

it('例外ボディのclient_secretをマスクする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(
            '{"error":"invalid_request","client_secret":"super-secret"}',
            400,
        ),
    ]);

    $caught = null;
    try {
        makeOAuthClient()->exchange('any');
    } catch (ChatworkRequestException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($caught?->body())->not->toContain('super-secret')
        ->and($caught?->body())->toContain('***');
});
