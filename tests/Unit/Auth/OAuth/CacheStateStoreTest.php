<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheStateStore;

beforeEach(function () {
    Config::set('cache.default', 'array');
});

it('round-trips put then pull within TTL', function () {
    $store = new CacheStateStore(Cache::store());

    $store->put('state-xyz', ['connection' => 'default', 'context' => ['user_id' => 7]], 600);

    expect($store->pull('state-xyz'))->toBe([
        'connection' => 'default',
        'context' => ['user_id' => 7],
    ]);
});

it('returns null when state was never stored', function () {
    /** @var CacheRepository $cache */
    $cache = Cache::store();
    $store = new CacheStateStore($cache);

    expect($store->pull('missing'))->toBeNull();
});

it('consumes state on pull so a second pull returns null', function () {
    $store = new CacheStateStore(Cache::store());

    $store->put('one-shot', ['connection' => 'default'], 600);

    expect($store->pull('one-shot'))->not->toBeNull();
    expect($store->pull('one-shot'))->toBeNull();
});
