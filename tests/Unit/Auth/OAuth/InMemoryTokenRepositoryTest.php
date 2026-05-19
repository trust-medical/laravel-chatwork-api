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

    $repo->save('sales', $token);

    expect($repo->find('sales')->accessToken)->toBe('a');
});

it('一度も保存されていないコネクション名で find すると null を返す', function () {
    expect((new InMemoryTokenRepository())->find('unknown'))->toBeNull();
});

it('testing 環境では通知を出さずに生成できる', function () {
    $message = null;
    set_error_handler(function (int $errno, string $errstr) use (&$message): bool {
        $message = $errstr;

        return true;
    }, E_USER_NOTICE);

    try {
        new InMemoryTokenRepository();
    } finally {
        restore_error_handler();
    }

    expect($message)->toBeNull();
});

it('testing/local 以外の環境では E_USER_NOTICE を発火する', function () {
    app()->detectEnvironment(fn () => 'production');

    $errno = null;
    $message = null;
    set_error_handler(function (int $no, string $str) use (&$errno, &$message): bool {
        $errno = $no;
        $message = $str;

        return true;
    }, E_USER_NOTICE);

    try {
        new InMemoryTokenRepository();
    } finally {
        restore_error_handler();
        app()->detectEnvironment(fn () => 'testing');
    }

    expect($errno)->toBe(E_USER_NOTICE)
        ->and($message)->toContain('production');
});
