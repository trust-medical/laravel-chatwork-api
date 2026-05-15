<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\IconPreset;

it('exposes all 17 preset values from the OpenAPI schema', function () {
    expect(IconPreset::cases())->toHaveCount(17);
});

it('uses lowercase string values matching the API', function () {
    expect(IconPreset::Group->value)->toBe('group');
    expect(IconPreset::Magcup->value)->toBe('magcup');
    expect(IconPreset::Travel->value)->toBe('travel');
});

it('rehydrates from the API string via from()', function () {
    expect(IconPreset::from('business'))->toBe(IconPreset::Business);
    expect(IconPreset::tryFrom('unknown'))->toBeNull();
});
