<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('asResult はクローンされた manager を返す (P1-T11)', function () {
    $root = Chatwork::getFacadeRoot();
    $asResult = Chatwork::asResult();

    expect($asResult)->not->toBe($root)
        ->and($asResult->getMode())->toBe(ResponseMode::Result)
        ->and($root->getMode())->toBe(ResponseMode::Dto);
});

it('asArray 呼び出し時にグローバルな manager の状態を変化させない', function () {
    Chatwork::asArray();

    expect(Chatwork::getFacadeRoot()->getMode())->toBe(ResponseMode::Dto);
});

it('モードチェーンは最後に指定したものが優先される', function () {
    $manager = Chatwork::asResult()->asArray();

    expect($manager->getMode())->toBe(ResponseMode::Array);
});

it('各アクセサーが期待通りのモードを返す', function () {
    expect(Chatwork::asArray()->getMode())->toBe(ResponseMode::Array)
        ->and(Chatwork::asDto()->getMode())->toBe(ResponseMode::Dto)
        ->and(Chatwork::asCollection()->getMode())->toBe(ResponseMode::Collection)
        ->and(Chatwork::asResponse()->getMode())->toBe(ResponseMode::Response)
        ->and(Chatwork::asPsrResponse()->getMode())->toBe(ResponseMode::PsrResponse)
        ->and(Chatwork::asResult()->getMode())->toBe(ResponseMode::Result);
});

it('モードチェーン全体を通じて接続状態を保持する', function () {
    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'sales-token',
    ]);

    $manager = Chatwork::connection('sales')->asArray();

    expect($manager->getEffectiveConnection()->name)->toBe('sales')
        ->and($manager->getMode())->toBe(ResponseMode::Array);
});
