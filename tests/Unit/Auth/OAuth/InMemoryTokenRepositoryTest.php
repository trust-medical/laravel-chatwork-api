<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\InMemoryTokenRepository;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenSet;

beforeEach(function () {
    Carbon::setTestNow('2026-05-15 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('コネクション名をキーに TokenSet を保存・取得できる', function () {
    $repo = new InMemoryTokenRepository();
    $token = new TokenSet('a', 'r', Carbon::now()->addHour()->toDateTimeImmutable());

    $repo->save($token, ['connection' => 'sales']);

    expect($repo->find('sales')->accessToken)->toBe('a');
});

it('一度も保存されていないコネクション名で find すると null を返す', function () {
    expect((new InMemoryTokenRepository())->find('unknown'))->toBeNull();
});
