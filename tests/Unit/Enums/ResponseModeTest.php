<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

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

it('fromConfig は null/空文字を未設定とみなしてデフォルトの Dto を返す', function () {
    expect(ResponseMode::fromConfig(null))->toBe(ResponseMode::Dto)
        ->and(ResponseMode::fromConfig(''))->toBe(ResponseMode::Dto);
});

it('fromConfig は有効な設定文字列を ResponseMode に解決する', function (string $value, ResponseMode $expected) {
    expect(ResponseMode::fromConfig($value))->toBe($expected);
})->with([
    ['array', ResponseMode::Array],
    ['dto', ResponseMode::Dto],
    ['collection', ResponseMode::Collection],
    ['response', ResponseMode::Response],
    ['psr_response', ResponseMode::PsrResponse],
    ['result', ResponseMode::Result],
]);

it('fromConfig は無効な設定値で ChatworkValidationException をスローする', function () {
    $caught = null;
    try {
        ResponseMode::fromConfig('bogus');
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class)
        ->and($caught?->violations())->toHaveKey('chatwork.response.mode');
});
