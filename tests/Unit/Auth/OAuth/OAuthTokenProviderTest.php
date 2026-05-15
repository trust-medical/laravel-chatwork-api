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
        oauth: new OAuthClient(new CacheStateStore(Cache::store()), config('chatwork.oauth')),
        leewaySeconds: $leeway,
    );
}

it('returns BearerTokenCredentials when a fresh token is stored', function () {
    $repo = new InMemoryTokenRepository();
    $repo->save(new TokenSet(
        accessToken: 'fresh-access',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addHour()->toDateTimeImmutable(),
    ), ['connection' => 'default']);

    $credentials = provider($repo)->credentials();

    expect($credentials)->toBeInstanceOf(BearerTokenCredentials::class);
    /** @var BearerTokenCredentials $credentials */
    expect($credentials->token)->toBe('fresh-access');
});

it('refreshes when stored token is past expiresAt', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save(new TokenSet(
        accessToken: 'old',
        refreshToken: 'old-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ), ['connection' => 'default']);

    $credentials = provider($repo, leeway: 0)->credentials();

    expect($credentials)->toBeInstanceOf(BearerTokenCredentials::class);
    /** @var BearerTokenCredentials $credentials */
    expect($credentials->token)->toBe('sample-access-token');
    Http::assertSent(fn ($r) => $r['grant_type'] === 'refresh_token');
});

it('refreshes when stored token is within the leeway window', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save(new TokenSet(
        accessToken: 'within-leeway',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addSeconds(30)->toDateTimeImmutable(),
    ), ['connection' => 'default']);

    provider($repo, leeway: 60)->credentials();

    Http::assertSent(fn ($r) => $r['grant_type'] === 'refresh_token');
});

it('persists refreshed TokenSet back to the repository', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save(new TokenSet(
        accessToken: 'old',
        refreshToken: 'old-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ), ['connection' => 'default']);

    provider($repo, leeway: 0)->credentials();

    $saved = $repo->find('default');
    expect($saved?->accessToken)->toBe('sample-access-token');
});

it('throws ChatworkAuthenticationException when no token is stored', function () {
    $repo = new InMemoryTokenRepository();

    $caught = null;
    try {
        provider($repo)->credentials();
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
});

it('throws ChatworkAuthenticationException when refresh fails', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-400.json'), 400),
    ]);

    $repo = new InMemoryTokenRepository();
    $repo->save(new TokenSet(
        accessToken: 'old',
        refreshToken: 'bad-refresh',
        expiresAt: Carbon::now()->subSecond()->toDateTimeImmutable(),
    ), ['connection' => 'default']);

    $caught = null;
    try {
        provider($repo, leeway: 0)->credentials();
    } catch (ChatworkAuthenticationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkAuthenticationException::class);
});

it('uses Cache::lock to coalesce concurrent refreshes', function () {
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
    $repo->save($expired, ['connection' => 'default']);

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
