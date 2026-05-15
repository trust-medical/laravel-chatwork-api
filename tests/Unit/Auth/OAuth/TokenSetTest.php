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

it('is a readonly class', function () {
    expect(TokenSet::class)->toBeReadonly();
});

it('returns false from isExpired when expiresAt is in the future', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addMinutes(10)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired())->toBeFalse();
});

it('returns true from isExpired after expiresAt', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->subSeconds(1)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired())->toBeTrue();
});

it('returns true from isExpired when within leeway window', function () {
    $tokenSet = new TokenSet(
        accessToken: 'a',
        refreshToken: 'r',
        expiresAt: Carbon::now()->addSeconds(30)->toDateTimeImmutable(),
    );

    expect($tokenSet->isExpired(leewaySeconds: 60))->toBeTrue();
    expect($tokenSet->isExpired(leewaySeconds: 0))->toBeFalse();
});

it('builds from API response payload via fromArray', function () {
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

it('round-trips through toArray and fromArray', function () {
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

it('throws InvalidArgumentException when fromArray receives incomplete data', function () {
    $caught = null;
    try {
        TokenSet::fromArray(['access_token' => 'a']);
    } catch (Throwable $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(InvalidArgumentException::class);
});
