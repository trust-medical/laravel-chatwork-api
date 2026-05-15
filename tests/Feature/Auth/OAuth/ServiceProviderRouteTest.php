<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use TrustMedical\LaravelChatworkApi\ChatworkServiceProvider;

it('does not register the callback route during boot when routes are disabled', function () {
    // The package's normal boot at app start saw routes_enabled=false (the default),
    // so the route is not registered.
    expect(Route::has('chatwork.oauth.callback'))->toBeFalse();
});

it('registers the callback route when routes_enabled is true and the helper is invoked', function () {
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

it('honors a custom route_prefix', function () {
    Config::set('chatwork.oauth.routes_enabled', true);
    Config::set('chatwork.oauth.route_prefix', 'auth/chatwork');

    $provider = app()->getProvider(ChatworkServiceProvider::class);
    expect($provider)->toBeInstanceOf(ChatworkServiceProvider::class);
    /** @var ChatworkServiceProvider $provider */
    $provider->registerOAuthRoutes();

    $route = Route::getRoutes()->getByName('chatwork.oauth.callback');
    expect($route?->uri())->toBe('auth/chatwork/callback');
});
