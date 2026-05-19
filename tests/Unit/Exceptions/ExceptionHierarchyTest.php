<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkConfigurationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('サブクラスを持たない例外は final で公開 API を固定する', function (string $class) {
    expect((new ReflectionClass($class))->isFinal())->toBeTrue();
})->with([
    ChatworkRequestException::class,
    ChatworkAuthenticationException::class,
    ChatworkRoutingException::class,
    ChatworkConfigurationException::class,
]);

it('ChatworkValidationException は ChatworkRoutingException に拡張されるため非 final を維持する', function () {
    expect((new ReflectionClass(ChatworkValidationException::class))->isFinal())->toBeFalse();
    expect((new ReflectionClass(ChatworkRoutingException::class))->getParentClass())
        ->not->toBeFalse();
});

it('全パッケージ例外は ChatworkException マーカーを実装する', function (string $class) {
    expect(is_a($class, ChatworkException::class, true))->toBeTrue();
})->with([
    ChatworkRequestException::class,
    ChatworkValidationException::class,
    ChatworkRoutingException::class,
    ChatworkAuthenticationException::class,
    ChatworkConfigurationException::class,
]);

it('catch (ChatworkException) で全パッケージ例外を一括捕捉できる', function () {
    $exceptions = [
        new ChatworkValidationException('v'),
        new ChatworkRoutingException('r'),
        new ChatworkAuthenticationException('a'),
        new ChatworkConfigurationException('c'),
        new ChatworkRequestException(500, 'GET', '/x', null, ''),
    ];

    foreach ($exceptions as $e) {
        $caught = null;
        try {
            throw $e;
        } catch (ChatworkException $x) {
            $caught = $x;
        }
        expect($caught)->toBe($e);
    }
});
