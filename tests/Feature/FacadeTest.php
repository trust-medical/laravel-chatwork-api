<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('resolves Chatwork facade to ChatworkManager (P1-T03)', function () {
    expect(Chatwork::getFacadeRoot())->toBeInstanceOf(ChatworkManager::class);
});

it('uses the chatwork facade accessor', function () {
    $root = Chatwork::getFacadeRoot();

    expect($root)->toBe(app('chatwork'));
});
