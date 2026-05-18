<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('Chatwork ファサードが ChatworkManager に解決される (P1-T03)', function () {
    expect(Chatwork::getFacadeRoot())->toBeInstanceOf(ChatworkManager::class);
});

it('chatwork ファサードアクセサーを使用する', function () {
    $root = Chatwork::getFacadeRoot();

    expect($root)->toBe(app('chatwork'));
});
