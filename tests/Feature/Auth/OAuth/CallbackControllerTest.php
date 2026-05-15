<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\Controllers\OAuthCallbackController;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\StateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Config::set('chatwork.oauth.client_id', 'client-abc');
    Config::set('chatwork.oauth.client_secret', 'super-secret');
    Config::set('chatwork.oauth.redirect_uri', 'https://app.example.com/callback');
    Config::set('chatwork.oauth.token_url', 'https://oauth.chatwork.com/token');
    Config::set('chatwork.oauth.redirect_after_callback', '/dashboard');
});

function buildController(StateStore $stateStore, TokenRepository $repo): OAuthCallbackController
{
    return new OAuthCallbackController(
        $stateStore,
        new OAuthClient($stateStore, config('chatwork.oauth')),
        $repo,
    );
}

function callbackRequest(string $queryString): Request
{
    return Request::create('/chatwork/oauth/callback?' . $queryString, 'GET');
}

it('redirects to the configured path on successful exchange', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $store->put('state-1', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-1&code=auth-code'));

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getStatusCode())->toBe(302);
    /** @var RedirectResponse $response */
    expect($response->getTargetUrl())->toEndWith('/dashboard');
});

it('saves the resulting TokenSet to the repository', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $store->put('state-2', ['connection' => 'oauth-conn', 'context' => []], 600);

    $controller(callbackRequest('state=state-2&code=auth-code'));

    $saved = $repo->find('oauth-conn');
    expect($saved)->not->toBeNull();
    expect($saved?->accessToken)->toBe('sample-access-token');
});

it('returns 400 when state is missing and never calls the token endpoint', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('code=auth-code'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('returns 400 when state cannot be resolved (replay or expired)', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('state=unknown-state&code=auth-code'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('returns 400 when provider includes an error parameter and never calls the token endpoint', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $store->put('state-3', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-3&error=access_denied'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('falls back to root path when redirect_after_callback is null', function () {
    Config::set('chatwork.oauth.redirect_after_callback', null);
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $store->put('state-4', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-4&code=auth-code'));

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    /** @var RedirectResponse $response */
    $host = parse_url($response->getTargetUrl(), PHP_URL_HOST);
    $path = parse_url($response->getTargetUrl(), PHP_URL_PATH) ?? '/';
    expect($host)->toBe('localhost');
    expect($path)->toBe('/');
});

it('falls back to root path when redirect_after_callback is a scheme-relative URL', function () {
    Config::set('chatwork.oauth.redirect_after_callback', '//evil.example.com/path');
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $store->put('state-5', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-5&code=auth-code'));

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    /** @var RedirectResponse $response */
    $host = parse_url($response->getTargetUrl(), PHP_URL_HOST);
    $path = parse_url($response->getTargetUrl(), PHP_URL_PATH) ?? '/';
    expect($host)->toBe('localhost');
    expect($path)->toBe('/');
});

it('returns a Response (Symfony) regardless of branch', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('state=missing-here&code=auth-code'));

    expect($response)->toBeInstanceOf(Response::class);
});
