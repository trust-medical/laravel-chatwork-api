<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkManager;

it('例外なく起動する (P1-T01)', function () {
    expect(app()->bound('chatwork'))->toBeTrue();
});

it('chatwork.php から設定をマージする (P1-T02)', function () {
    expect(config('chatwork.base_uri'))->toBe('https://api.chatwork.com/v2')
        ->and(config('chatwork.timeout'))->toBe(10)
        ->and(config('chatwork.default'))->toBe('default')
        ->and(config('chatwork.response.mode'))->toBe('dto')
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
