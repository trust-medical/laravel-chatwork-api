<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\ChatworkServiceProvider;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('registers the ChatworkServiceProvider', function () {
    expect(class_exists(ChatworkServiceProvider::class))->toBeTrue();
});

it('exposes the Chatwork facade', function () {
    expect(class_exists(Chatwork::class))->toBeTrue();
});

it('binds chatwork to ChatworkManager in the container', function () {
    expect(app()->bound('chatwork'))->toBeTrue()
        ->and(app('chatwork'))->toBeInstanceOf(ChatworkManager::class);
});

it('has ChatworkClient class', function () {
    expect(class_exists(ChatworkClient::class))->toBeTrue();
});

it('publishes the config file', function () {
    expect(config('chatwork.base_uri'))->toBe('https://api.chatwork.com/v2')
        ->and(config('chatwork.timeout'))->toBe(10)
        ->and(config('chatwork.oauth.routes_enabled'))->toBeFalse();
});
