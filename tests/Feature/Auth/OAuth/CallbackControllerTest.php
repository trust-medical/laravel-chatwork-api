<?php

declare(strict_types=1);

use Illuminate\Contracts\Config\Repository;
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
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;

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
        app(Repository::class),
    );
}

function callbackRequest(string $queryString): Request
{
    return Request::create('/chatwork/oauth/callback?' . $queryString, 'GET');
}

it('交換成功時に設定済みパスへリダイレクトする', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $store->put('state-1', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-1&code=auth-code'));

    expect($response)->toBeInstanceOf(RedirectResponse::class);
    expect($response->getStatusCode())->toBe(302);
    /** @var RedirectResponse $response */
    expect($response->getTargetUrl())->toEndWith('/dashboard');
});

it('取得したTokenSetをリポジトリへ保存する', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $store->put('state-2', ['connection' => 'oauth-conn', 'context' => []], 600);

    $controller(callbackRequest('state=state-2&code=auth-code'));

    $saved = $repo->find('oauth-conn');
    expect($saved)->not->toBeNull();
    expect($saved?->accessToken)->toBe('sample-access-token');
});

it('stateが欠落している場合は400を返しtokenエンドポイントを呼ばない', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('code=auth-code'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('stateが解決できない場合（リプレイまたは期限切れ）は400を返す', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('state=unknown-state&code=auth-code'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('プロバイダがerrorパラメータを含む場合は400を返しtokenエンドポイントを呼ばない', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $store->put('state-3', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-3&error=access_denied'));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('redirect_after_callbackがnullの場合はルートパスにフォールバックする', function () {
    Config::set('chatwork.oauth.redirect_after_callback', null);
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
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

it('redirect_after_callbackがスキーム相対URLの場合はルートパスにフォールバックする', function () {
    Config::set('chatwork.oauth.redirect_after_callback', '//evil.example.com/path');
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
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

it('安全制限を超える長さのcodeを拒否しtokenエンドポイントをスキップする', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $store->put('state-long', ['connection' => 'default', 'context' => []], 600);

    $tooLong = str_repeat('a', 1025);
    $response = $controller(callbackRequest("state=state-long&code={$tooLong}"));

    expect($response->getStatusCode())->toBe(400);
    Http::assertNothingSent();
});

it('どの分岐でもResponse (Symfony) を返す', function () {
    Http::fake();

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $response = $controller(callbackRequest('state=missing-here&code=auth-code'));

    expect($response)->toBeInstanceOf(Response::class);
});

it('コード交換が失敗した場合は502を返し秘密情報を漏らさない', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(
            '{"error":"invalid_grant","client_secret":"super-secret"}',
            400,
        ),
    ]);

    $store = new CacheStateStore(Cache::store());
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $controller = buildController($store, $repo);

    $store->put('state-err', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-err&code=auth-code-xyz'));

    expect($response->getStatusCode())->toBe(502);
    $body = (string) $response->getContent();
    expect($body)->toContain('token_exchange_failed');
    expect(str_contains($body, 'super-secret'))->toBeFalse();
    expect(str_contains($body, 'auth-code-xyz'))->toBeFalse();
    expect($repo->find('default'))->toBeNull();
});

it('トークン保存が設定不備で失敗した場合は500を返す', function () {
    Http::fake([
        'oauth.chatwork.com/token' => Http::response(fixtureJson('oauth/token-200.json'), 200),
    ]);

    $store = new CacheStateStore(Cache::store());
    $failingRepo = new class() implements TokenRepository
    {
        public function save(TokenSet $tokenSet, array $context = []): void
        {
            throw new InvalidArgumentException('connection misconfigured');
        }

        public function find(string $connectionName): ?TokenSet
        {
            return null;
        }
    };
    $controller = buildController($store, $failingRepo);

    $store->put('state-save', ['connection' => 'default', 'context' => []], 600);

    $response = $controller(callbackRequest('state=state-save&code=auth-code'));

    expect($response->getStatusCode())->toBe(500);
    expect((string) $response->getContent())->toContain('oauth_misconfigured');
});
