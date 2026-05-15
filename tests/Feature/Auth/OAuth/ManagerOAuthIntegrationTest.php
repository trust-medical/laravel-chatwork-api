<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.base_uri', 'https://api.chatwork.com/v2');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
    Config::set('chatwork.oauth.refresh_leeway_seconds', 60);
    Config::set('chatwork.connections.oauth-conn', ['auth' => 'oauth']);
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('sends Authorization Bearer header from the stored TokenSet', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    app(TokenRepository::class)->save(new TokenSet(
        accessToken: 'stored-access-token',
        refreshToken: 'stored-refresh',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ), ['connection' => 'oauth-conn']);

    Chatwork::connection('oauth-conn')->rooms()->messages()->create(123, 'Hello');

    Http::assertSent(function (Request $request) {
        return $request->hasHeader('Authorization', 'Bearer stored-access-token')
            && ! $request->hasHeader('x-chatworktoken');
    });
});

it('refreshes the token before calling the API when expired', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
        'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
            fixtureJson('messages/create-message-201.json'),
            201,
        ),
    ]);

    app(TokenRepository::class)->save(new TokenSet(
        accessToken: 'expired-access',
        refreshToken: 'old-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ), ['connection' => 'oauth-conn']);

    Chatwork::connection('oauth-conn')->rooms()->messages()->create(123, 'Hi');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://oauth.chatwork.com/token'
            && $request['grant_type'] === 'refresh_token';
    });
    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
            && $request->hasHeader('Authorization', 'Bearer sample-access-token');
    });
});

it('throws ChatworkAuthenticationException when refresh fails', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-400.json'), 400),
    ]);

    app(TokenRepository::class)->save(new TokenSet(
        accessToken: 'expired-access',
        refreshToken: 'bad-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ), ['connection' => 'oauth-conn']);

    $caught = null;
    try {
        Chatwork::connection('oauth-conn')->rooms()->messages()->create(123, 'Hi');
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
});
