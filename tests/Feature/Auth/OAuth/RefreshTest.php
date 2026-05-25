<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
});

function refreshClient(): OAuthClient
{
    return new OAuthClient(new CacheStateStore(Cache::store()), config('chatwork.oauth'));
}

it('refresh_token grantで/tokenへPOSTする (Confidential = HTTP Basic 認証)', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    refreshClient()->refresh('refresh-token-xyz');

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $expectedAuth = 'Basic ' . base64_encode('client-abc:super-secret');

        return $request->method() === 'POST'
            && $request->url() === 'https://oauth.chatwork.com/token'
            && $request->hasHeader('Authorization', $expectedAuth)
            && $body['grant_type'] === 'refresh_token'
            && $body['refresh_token'] === 'refresh-token-xyz'
            && ! array_key_exists('client_id', $body)
            && ! array_key_exists('client_secret', $body);
    });
});

it('Public client (client_secret 未設定) の refresh は body に client_id', function () {
    Config::set('chatwork.oauth.client_secret', null);

    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    refreshClient()->refresh('refresh-token-xyz');

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return $request->method() === 'POST'
            && ! $request->hasHeader('Authorization')
            && $body['grant_type'] === 'refresh_token'
            && $body['refresh_token'] === 'refresh-token-xyz'
            && $body['client_id'] === 'client-abc'
            && ! array_key_exists('client_secret', $body);
    });
});

it('成功時にTokenSetを返す', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $tokenSet = refreshClient()->refresh('refresh-token-xyz');

    expect($tokenSet)->toBeInstanceOf(TokenSet::class);
    expect($tokenSet->accessToken)->toBe('sample-access-token');
});

it('invalid_grantのときChatworkAuthenticationExceptionをスローする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-400.json'), 400),
    ]);

    $caught = null;
    try {
        refreshClient()->refresh('expired');
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class)
        ->and($caught?->getPrevious())->toBeInstanceOf(ChatworkRequestException::class);
});

it('例外ボディのrefresh_tokenをマスクする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(
            '{"error":"invalid_grant","refresh_token":"leaky-refresh"}',
            400,
        ),
    ]);

    $previous = null;
    try {
        refreshClient()->refresh('leaky-refresh');
    } catch (ChatworkAuthenticationException $e) {
        $candidate = $e->getPrevious();
        if ($candidate instanceof ChatworkRequestException) {
            $previous = $candidate;
        }
    }

    expect($previous)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($previous?->body())->not->toContain('leaky-refresh')
        ->and($previous?->body())->toContain('***');
});
