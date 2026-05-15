<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

it('parses config string values', function () {
    expect(ResponseMode::from('dto'))->toBe(ResponseMode::Dto)
        ->and(ResponseMode::from('array'))->toBe(ResponseMode::Array)
        ->and(ResponseMode::from('collection'))->toBe(ResponseMode::Collection)
        ->and(ResponseMode::from('response'))->toBe(ResponseMode::Response)
        ->and(ResponseMode::from('psr_response'))->toBe(ResponseMode::PsrResponse)
        ->and(ResponseMode::from('result'))->toBe(ResponseMode::Result);
});

it('matches the default mode declared in config/chatwork.php', function () {
    expect(ResponseMode::from(config('chatwork.response.mode')))->toBe(ResponseMode::Dto);
});
