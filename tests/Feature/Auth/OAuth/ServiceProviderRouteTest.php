<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use TrustMedical\LaravelChatworkApi\ChatworkServiceProvider;

it('routesが無効な場合はboot時にcallbackルートを登録しない', function () {
    // パッケージの通常boot時にroutes_enabled=false（デフォルト）が検出されるため、ルートは登録されない。
    expect(Route::has('chatwork.oauth.callback'))->toBeFalse();
});

it('routes_enabledがtrueかつヘルパ呼び出し時にcallbackルートを登録する', function () {
    Config::set('chatwork.oauth.routes_enabled', true);
    Config::set('chatwork.oauth.route_prefix', 'chatwork/oauth');

    $provider = app()->getProvider(ChatworkServiceProvider::class);
    expect($provider)->toBeInstanceOf(ChatworkServiceProvider::class);
    /** @var ChatworkServiceProvider $provider */
    $provider->registerOAuthRoutes();

    expect(Route::has('chatwork.oauth.callback'))->toBeTrue();

    $route = Route::getRoutes()->getByName('chatwork.oauth.callback');
    expect($route)->not->toBeNull();
    expect($route?->uri())->toBe('chatwork/oauth/callback');
});

it('カスタムroute_prefixを反映する', function () {
    Config::set('chatwork.oauth.routes_enabled', true);
    Config::set('chatwork.oauth.route_prefix', 'auth/chatwork');

    $provider = app()->getProvider(ChatworkServiceProvider::class);
    expect($provider)->toBeInstanceOf(ChatworkServiceProvider::class);
    /** @var ChatworkServiceProvider $provider */
    $provider->registerOAuthRoutes();

    $route = Route::getRoutes()->getByName('chatwork.oauth.callback');
    expect($route?->uri())->toBe('auth/chatwork/callback');
});
