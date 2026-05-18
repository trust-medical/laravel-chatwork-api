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

function cacheTokenKey(string $connection): string
{
    return 'chatwork:oauth:token:' . hash('sha256', $connection);
}

it('TokenSet を暗号化してキャッシュへ保存し復元できる', function () {
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
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
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());

    expect($repo->find('never-saved'))->toBeNull();
});

it('キャッシュ生値は平文トークンを含まない暗号文字列である', function () {
    $repo = new CacheTokenRepository(Cache::store(), testEncrypter());
    $repo->save(
        new TokenSet('plain-access-x', 'plain-refresh-y', Carbon::now()->addHour()->toDateTimeImmutable()),
        ['connection' => 'c'],
    );

    $raw = Cache::get(cacheTokenKey('c'));

    expect(is_string($raw))->toBeTrue();
    expect(str_contains((string) $raw, 'plain-access-x'))->toBeFalse();
    expect(str_contains((string) $raw, 'plain-refresh-y'))->toBeFalse();
});

it('復号できない / 平文 / 不完全なキャッシュ値では find が例外を投げず null を返す', function () {
    $encrypter = testEncrypter();
    $repo = new CacheTokenRepository(Cache::store(), $encrypter);
    $key = cacheTokenKey('c');

    Cache::forever($key, 'not-a-valid-ciphertext');
    expect($repo->find('c'))->toBeNull();

    Cache::forever($key, ['access_token' => 'legacy-plaintext']);
    expect($repo->find('c'))->toBeNull();

    Cache::forever($key, $encrypter->encrypt(['access_token' => 'only-one-key']));
    expect($repo->find('c'))->toBeNull();
});
