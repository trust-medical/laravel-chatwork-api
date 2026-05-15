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

it('POSTs to /token with authorization_code grant', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    makeOAuthClient()->exchange('auth-code-123');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://oauth.chatwork.com/token'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'auth-code-123'
            && $request['client_id'] === 'client-abc'
            && $request['client_secret'] === 'super-secret'
            && $request['redirect_uri'] === 'https://app.example.com/callback'
            && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
    });
});

it('returns a TokenSet parsed from the response', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $tokenSet = makeOAuthClient()->exchange('auth-code-123');

    expect($tokenSet)->toBeInstanceOf(TokenSet::class);
    expect($tokenSet->accessToken)->toBe('sample-access-token');
    expect($tokenSet->refreshToken)->toBe('sample-refresh-token');
});

it('throws ChatworkRequestException on 400 invalid_grant', function () {
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

it('redacts client_secret in exception body', function () {
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
