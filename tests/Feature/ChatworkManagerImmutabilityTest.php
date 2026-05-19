<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

/**
 * ChatworkManager is bound as a container singleton. Every connection /
 * credential / mode "with" method must return a clone and never mutate the
 * shared singleton, otherwise long-running runtimes (Octane / Swoole / queue
 * workers) leak state between requests. These assertions pin that invariant
 * so a future mutating method is caught as a regression rather than as a
 * silent, hard-to-observe BC break.
 */
beforeEach(function () {
    config()->set('chatwork.default', 'default');
    config()->set('chatwork.connections.default', ['auth' => 'api_token', 'token' => 'default-token']);
    config()->set('chatwork.connections.sales', ['auth' => 'bearer', 'token' => 'sales-token']);
});

it('the facade root is the same container singleton instance', function () {
    expect(app('chatwork'))->toBe(app('chatwork'))
        ->and(Chatwork::getFacadeRoot())->toBe(app('chatwork'))
        ->and(app('chatwork'))->toBeInstanceOf(ChatworkManager::class);
});

it('every with-method returns a new instance distinct from the singleton', function () {
    $root = Chatwork::getFacadeRoot();

    expect(Chatwork::connection('sales'))->not->toBe($root)
        ->and(Chatwork::forConnection($root->getConnection()))->not->toBe($root)
        ->and(Chatwork::withApiToken('temp'))->not->toBe($root)
        ->and(Chatwork::withBearerToken('temp'))->not->toBe($root)
        ->and(Chatwork::asArray())->not->toBe($root)
        ->and(Chatwork::asDto())->not->toBe($root)
        ->and(Chatwork::asCollection())->not->toBe($root)
        ->and(Chatwork::asResponse())->not->toBe($root)
        ->and(Chatwork::asPsrResponse())->not->toBe($root)
        ->and(Chatwork::asResult())->not->toBe($root);
});

it('mode with-methods never mutate the shared singleton', function () {
    Chatwork::asResult();
    Chatwork::asArray();
    Chatwork::asCollection()->asPsrResponse();

    expect(app('chatwork')->getMode())->toBe(ResponseMode::Dto);
});

it('connection / credential with-methods never mutate the shared singleton', function () {
    Chatwork::connection('sales');
    Chatwork::withApiToken('leaked-token');
    Chatwork::withBearerToken('leaked-token');

    $root = app('chatwork');

    expect($root->getEffectiveConnection()->name)->toBe('default')
        ->and($root->getMode())->toBe(ResponseMode::Dto)
        ->and(Chatwork::getFacadeRoot())->toBe($root);
});

it('isolates state between two simulated requests sharing the singleton', function () {
    // Request A and Request B reuse the same bound singleton (Octane scenario).
    $a = Chatwork::connection('sales')->withApiToken('token-a')->asArray();
    $b = Chatwork::connection('default')->asResult();

    expect($a)->not->toBe($b)
        ->and($a->getMode())->toBe(ResponseMode::Array)
        ->and($b->getMode())->toBe(ResponseMode::Result)
        ->and($b->getEffectiveConnection()->name)->toBe('default')
        // The singleton itself is untouched by either "request".
        ->and(app('chatwork')->getMode())->toBe(ResponseMode::Dto);
});

it('keeps each link of a with-chain an independent instance', function () {
    $base = Chatwork::getFacadeRoot();
    $withMode = $base->asResult();
    $rebound = $withMode->asArray();

    expect($withMode)->not->toBe($base)
        ->and($rebound)->not->toBe($withMode)
        ->and($base->getMode())->toBe(ResponseMode::Dto)
        ->and($withMode->getMode())->toBe(ResponseMode::Result)
        ->and($rebound->getMode())->toBe(ResponseMode::Array);
});
