<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('returns a cloned manager from asResult (P1-T11)', function () {
    $root = Chatwork::getFacadeRoot();
    $asResult = Chatwork::asResult();

    expect($asResult)->not->toBe($root)
        ->and($asResult->getMode())->toBe(ResponseMode::Result)
        ->and($root->getMode())->toBe(ResponseMode::Dto);
});

it('does not mutate the global manager state when asArray is called', function () {
    Chatwork::asArray();

    expect(Chatwork::getFacadeRoot()->getMode())->toBe(ResponseMode::Dto);
});

it('chains modes with last-wins semantics', function () {
    $manager = Chatwork::asResult()->asArray();

    expect($manager->getMode())->toBe(ResponseMode::Array);
});

it('returns the expected mode for each accessor', function () {
    expect(Chatwork::asArray()->getMode())->toBe(ResponseMode::Array)
        ->and(Chatwork::asDto()->getMode())->toBe(ResponseMode::Dto)
        ->and(Chatwork::asCollection()->getMode())->toBe(ResponseMode::Collection)
        ->and(Chatwork::asResponse()->getMode())->toBe(ResponseMode::Response)
        ->and(Chatwork::asPsrResponse()->getMode())->toBe(ResponseMode::PsrResponse)
        ->and(Chatwork::asResult()->getMode())->toBe(ResponseMode::Result);
});

it('preserves connection state across mode chain', function () {
    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'sales-token',
    ]);

    $manager = Chatwork::connection('sales')->asArray();

    expect($manager->getEffectiveConnection()->name)->toBe('sales')
        ->and($manager->getMode())->toBe(ResponseMode::Array);
});
