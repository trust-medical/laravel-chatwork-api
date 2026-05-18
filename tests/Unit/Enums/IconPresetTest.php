<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\IconPreset;

it('OpenAPI スキーマで定義された 17 個のプリセット値をすべて公開する', function () {
    expect(IconPreset::cases())->toHaveCount(17);
});

it('API と一致する小文字の文字列値を使用する', function () {
    expect(IconPreset::Group->value)->toBe('group');
    expect(IconPreset::Magcup->value)->toBe('magcup');
    expect(IconPreset::Travel->value)->toBe('travel');
});

it('from() で API 文字列からケースを復元できる', function () {
    expect(IconPreset::from('business'))->toBe(IconPreset::Business);
    expect(IconPreset::tryFrom('unknown'))->toBeNull();
});
