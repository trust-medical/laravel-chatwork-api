<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

it('設定ファイルの文字列値をパースできる', function () {
    expect(ResponseMode::from('dto'))->toBe(ResponseMode::Dto)
        ->and(ResponseMode::from('array'))->toBe(ResponseMode::Array)
        ->and(ResponseMode::from('collection'))->toBe(ResponseMode::Collection)
        ->and(ResponseMode::from('response'))->toBe(ResponseMode::Response)
        ->and(ResponseMode::from('psr_response'))->toBe(ResponseMode::PsrResponse)
        ->and(ResponseMode::from('result'))->toBe(ResponseMode::Result);
});

it('config/chatwork.php で宣言されたデフォルトモードと一致する', function () {
    expect(ResponseMode::from(config('chatwork.response.mode')))->toBe(ResponseMode::Dto);
});
