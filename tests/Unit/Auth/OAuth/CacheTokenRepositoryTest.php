<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\CacheTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;

beforeEach(function () {
    Config::set('cache.default', 'array');
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('TokenSet をキャッシュ経由で保存・復元できる', function () {
    $repo = new CacheTokenRepository(Cache::store());
    $token = new TokenSet(
        accessToken: 'access',
        refreshToken: 'refresh',
        expiresAt: Carbon::now()->addHours(2)->toDateTimeImmutable(),
    );

    $repo->save($token, ['connection' => 'oauth-conn']);

    $loaded = $repo->find('oauth-conn');

    expect($loaded)->not->toBeNull();
    expect($loaded->accessToken)->toBe('access');
    expect($loaded->refreshToken)->toBe('refresh');
    expect($loaded->expiresAt->getTimestamp())->toBe($token->expiresAt->getTimestamp());
});

it('保存されていないコネクション名で find すると null を返す', function () {
    $repo = new CacheTokenRepository(Cache::store());

    expect($repo->find('never-saved'))->toBeNull();
});
