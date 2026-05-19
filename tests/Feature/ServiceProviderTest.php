<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\InMemoryTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\StateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkConfigurationException;

it('例外なく起動する (P1-T01)', function () {
    expect(app()->bound('chatwork'))->toBeTrue();
});

it('chatwork.php から設定をマージする (P1-T02)', function () {
    expect(config('chatwork.base_uri'))->toBe('https://api.chatwork.com/v2')
        ->and(config('chatwork.timeout'))->toBe(10)
        ->and(config('chatwork.default'))->toBe('default')
        ->and(config('chatwork.response.mode'))->toBe('dto')
        ->and(config('chatwork.oauth.timeout'))->toBe(10)
        ->and(config('chatwork.oauth.routes_enabled'))->toBeFalse();
});

it('chatwork シングルトンを ChatworkManager にバインドする', function () {
    $first = app('chatwork');
    $second = app('chatwork');

    expect($first)->toBeInstanceOf(ChatworkManager::class)
        ->and($second)->toBe($first);
});

it('エイリアス経由で ChatworkManager::class を直接解決できる', function () {
    expect(app(ChatworkManager::class))->toBeInstanceOf(ChatworkManager::class);
});

it('token_repository が未設定なら既定の CacheTokenRepository を解決する', function () {
    expect(app(TokenRepository::class))->toBeInstanceOf(CacheTokenRepository::class);
});

it('state_store が未設定なら既定の CacheStateStore を解決する', function () {
    expect(app(StateStore::class))->toBeInstanceOf(CacheStateStore::class);
});

it('token_repository に有効なカスタム実装を指定すると差し替わる', function () {
    config()->set('chatwork.oauth.token_repository', InMemoryTokenRepository::class);

    expect(app(TokenRepository::class))->toBeInstanceOf(InMemoryTokenRepository::class);
});

it('token_repository に存在しないクラスを指定すると ChatworkConfigurationException', function () {
    config()->set('chatwork.oauth.token_repository', 'TrustMedical\\NoSuch\\Repository');

    expect(fn () => app(TokenRepository::class))
        ->toThrow(ChatworkConfigurationException::class);
});

it('token_repository が契約 interface 未実装なら ChatworkConfigurationException', function () {
    config()->set('chatwork.oauth.token_repository', stdClass::class);

    expect(fn () => app(TokenRepository::class))
        ->toThrow(ChatworkConfigurationException::class);
});

it('state_store が契約 interface 未実装なら ChatworkConfigurationException', function () {
    config()->set('chatwork.oauth.state_store', stdClass::class);

    expect(fn () => app(StateStore::class))
        ->toThrow(ChatworkConfigurationException::class);
});
