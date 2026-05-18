<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;

beforeEach(function () {
    Config::set('cache.default', 'array');
});

it('TTL 内で put した値を pull で取得できる', function () {
    $store = new CacheStateStore(Cache::store());

    $store->put('state-xyz', ['connection' => 'default', 'context' => ['user_id' => 7]], 600);

    expect($store->pull('state-xyz'))->toBe([
        'connection' => 'default',
        'context' => ['user_id' => 7],
    ]);
});

it('一度も保存されていない state を pull すると null を返す', function () {
    /** @var CacheRepository $cache */
    $cache = Cache::store();
    $store = new CacheStateStore($cache);

    expect($store->pull('missing'))->toBeNull();
});

it('pull で state を消費するため 2 回目の pull は null を返す', function () {
    $store = new CacheStateStore(Cache::store());

    $store->put('one-shot', ['connection' => 'default'], 600);

    expect($store->pull('one-shot'))->not->toBeNull();
    expect($store->pull('one-shot'))->toBeNull();
});
