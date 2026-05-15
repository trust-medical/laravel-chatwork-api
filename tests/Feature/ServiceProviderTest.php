<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkManager;

it('boots without exception (P1-T01)', function () {
    expect(app()->bound('chatwork'))->toBeTrue();
});

it('merges config from chatwork.php (P1-T02)', function () {
    expect(config('chatwork.base_uri'))->toBe('https://api.chatwork.com/v2')
        ->and(config('chatwork.timeout'))->toBe(10)
        ->and(config('chatwork.default'))->toBe('default')
        ->and(config('chatwork.response.mode'))->toBe('dto')
        ->and(config('chatwork.oauth.routes_enabled'))->toBeFalse();
});

it('binds chatwork singleton to ChatworkManager', function () {
    $first = app('chatwork');
    $second = app('chatwork');

    expect($first)->toBeInstanceOf(ChatworkManager::class)
        ->and($second)->toBe($first);
});

it('also resolves ChatworkManager::class directly via alias', function () {
    expect(app(ChatworkManager::class))->toBeInstanceOf(ChatworkManager::class);
});
