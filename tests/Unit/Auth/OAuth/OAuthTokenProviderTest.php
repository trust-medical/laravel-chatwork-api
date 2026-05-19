<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\InMemoryTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthTokenProvider;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function provider(InMemoryTokenRepository $repo, int $leeway = 60): OAuthTokenProvider
{
    return new OAuthTokenProvider(
        connectionName: 'default',
        repository: $repo,
        oauth: new OAuthClient(new CacheStateStore(Cache::store()), (array) config('chatwork.oauth')),
        leewaySeconds: $leeway,
    );
}

it('有効期限内のトークンが保存済みの場合は BearerTokenCredentials を返す', function () {
    Http::fake(['api.chatwork.com/*' => Http::response([], 200)]);

    $repo = new InMemoryTokenRepository();
    $repo->save('default', new TokenSet(
        accessToken: 'fresh-access',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ));

    $credentials = provider($repo)->credentials();

    expect($credentials)->toBeInstanceOf(BearerTokenCredentials::class);

    $credentials->applyTo(Http::baseUrl('https://api.chatwork.com/v2'))->get('/me');
    Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer fresh-access'));
});

it('保存済みトークンが expiresAt を過ぎている場合はリフレッシュする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
        'api.chatwork.com/*' => Http::response([], 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save('default', new TokenSet(
        accessToken: 'old',
        refreshToken: 'old-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ));

    $credentials = provider($repo, leeway: 0)->credentials();

    expect($credentials)->toBeInstanceOf(BearerTokenCredentials::class);

    $credentials->applyTo(Http::baseUrl('https://api.chatwork.com/v2'))->get('/me');
    Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sample-access-token'));
    Http::assertSent(fn ($r) => ($r['grant_type'] ?? null) === 'refresh_token');
});

it('保存済みトークンが leeway ウィンドウ内にある場合もリフレッシュする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save('default', new TokenSet(
        accessToken: 'within-leeway',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addSeconds(30)->toDateTimeImmutable(),
    ));

    provider($repo, leeway: 60)->credentials();

    Http::assertSent(fn ($r) => $r['grant_type'] === 'refresh_token');
});

it('リフレッシュ後の TokenSet をリポジトリに永続化する', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save('default', new TokenSet(
        accessToken: 'old',
        refreshToken: 'old-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ));

    provider($repo, leeway: 0)->credentials();

    $saved = $repo->find('default');
    expect($saved?->accessToken)->toBe('sample-access-token');
});

it('トークンが未保存の場合は ChatworkAuthenticationException をスローする', function () {
    $repo = new InMemoryTokenRepository();

    $caught = null;
    try {
        provider($repo)->credentials();
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
});

it('リフレッシュが失敗した場合は ChatworkAuthenticationException をスローする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-400.json'), 400),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save('default', new TokenSet(
        accessToken: 'old',
        refreshToken: 'bad-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ));

    $caught = null;
    try {
        provider($repo, leeway: 0)->credentials();
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
});

it('Cache::lock で同時リフレッシュを一本化する', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::sequence()
            ->push(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $expired = new TokenSet(
        accessToken: 'old',
        refreshToken: 'r',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    );
    $repo->save('default', $expired);

    $lockKey = 'chatwork:oauth:refresh:' . hash('sha256', 'default');
    $heldLock = Cache::lock($lockKey, 10);
    $heldLock->get();

    Cache::store()->forever('chatwork:oauth:simulate-other-worker', true);

    $caught = null;
    try {
        provider($repo, leeway: 0)->credentials();
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    } finally {
        $heldLock->release();
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
    Http::assertNothingSent();
});

it('リフレッシュロック TTL は OAuth timeout 既定値より十分長い', function () {
    $ttl = (new ReflectionClass(OAuthTokenProvider::class))->getConstant('LOCK_TTL_SECONDS');

    expect($ttl)->toBe(30);
});
