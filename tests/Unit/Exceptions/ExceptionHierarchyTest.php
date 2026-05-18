<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('サブクラスを持たない例外は final で公開 API を固定する', function (string $class) {
    expect((new ReflectionClass($class))->isFinal())->toBeTrue();
})->with([
    ChatworkRequestException::class,
    ChatworkAuthenticationException::class,
    ChatworkRoutingException::class,
]);

it('ChatworkValidationException は ChatworkRoutingException に拡張されるため非 final を維持する', function () {
    expect((new ReflectionClass(ChatworkValidationException::class))->isFinal())->toBeFalse();
    expect((new ReflectionClass(ChatworkRoutingException::class))->getParentClass())
        ->not->toBeFalse();
});
