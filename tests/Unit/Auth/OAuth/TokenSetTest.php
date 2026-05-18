<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;

beforeEach(function () {
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('readonly クラスである', function () {
    expect(TokenSet::class)->toBeReadonly();
});

it('expiresAt が未来の場合 isExpired() は false を返す', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addMinutes(10)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired())->toBeFalse();
});

it('expiresAt を過ぎた後は isExpired() が true を返す', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->subSeconds(1)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired())->toBeTrue();
});

it('leeway ウィンドウ内にある場合も isExpired() は true を返す', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addSeconds(30)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired(leewaySeconds: 60))->toBeTrue();
    expect($tokenSet->isExpired(leewaySeconds: 0))->toBeFalse();
});

it('fromArray() で API レスポンスのペイロードから生成できる', function () {
    $tokenSet = TokenSet::fromArray([
        'access_token' => 'sample-access-token',
        'refresh_token' => 'sample-refresh-token',
        'token_type' => 'Bearer',
        'expires_in' => 86400,
    ]);

    expect($tokenSet->accessToken)->toBe('sample-access-token');
    expect($tokenSet->refreshToken)->toBe('sample-refresh-token');
    expect($tokenSet->tokenType)->toBe('Bearer');
    expect($tokenSet->expiresAt->format('Y-m-d H:i:s'))
        ->toBe(Carbon::now()->addSeconds(86400)->format('Y-m-d H:i:s'));
});

it('toArray() と fromArray() で相互変換できる', function () {
    $original = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addHours(2)->toDateTimeImmutable(),
        tokenType: 'Bearer',
    );

    $array = $original->toArray();

    expect($array)->toHaveKeys(['access_token', 'refresh_token', 'expires_at', 'token_type']);

    $restored = TokenSet::fromArray($array);

    expect($restored->accessToken)->toBe($original->accessToken);
    expect($restored->refreshToken)->toBe($original->refreshToken);
    expect($restored->tokenType)->toBe($original->tokenType);
    expect($restored->expiresAt->getTimestamp())->toBe($original->expiresAt->getTimestamp());
});

it('fromArray() に不完全なデータを渡すと InvalidArgumentException をスローする', function () {
    $caught = null;
    try {
        TokenSet::fromArray(['access_token' => 'a']);
    } catch (Throwable $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(InvalidArgumentException::class);
});
