<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\ValidatesBodyLength;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * private static なトレイトメソッドを検証するためのテスト用ハーネス。
 */
class BodyLengthHarness
{
    use ValidatesBodyLength;

    public static function run(string $body, int $min, int $max, string $label): void
    {
        self::assertBodyLength($body, $min, $max, $label);
    }
}

it('下限未満の本文を拒否しラベルを反映する', function () {
    expect(fn () => BodyLengthHarness::run('', 1, 10, 'Message'))
        ->toThrow(ChatworkValidationException::class, 'Message body must not be empty.');
});

it('上限超過の本文を拒否しラベルと上限値を反映する', function () {
    expect(fn () => BodyLengthHarness::run(str_repeat('a', 11), 1, 10, 'Task'))
        ->toThrow(ChatworkValidationException::class, 'Task body must be 10 characters or less.');
});

it('下限ちょうど・上限ちょうどは通過する', function () {
    BodyLengthHarness::run('a', 1, 10, 'Message');
    BodyLengthHarness::run(str_repeat('a', 10), 1, 10, 'Message');

    expect(true)->toBeTrue();
});

it('violations は body キー固定である', function () {
    try {
        BodyLengthHarness::run('', 1, 10, 'Message');
    } catch (ChatworkValidationException $e) {
        expect($e->violations())->toHaveKey('body');

        return;
    }

    throw new RuntimeException('ChatworkValidationException を期待');
});
