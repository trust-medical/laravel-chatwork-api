<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Http\Result;

/**
 * @param  array<int|string, mixed>|string  $body
 * @param  array<string, string|int>  $headers
 */
function makeResult(int $status, array|string $body = [], array $headers = []): Result
{
    Http::fake([
        'https://api.chatwork.com/v2/probe' => Http::response($body, $status, $headers),
    ]);

    $response = Http::get('https://api.chatwork.com/v2/probe');

    return new Result($response, 'GET', '/probe', 'probe');
}

it('reports succeeded for 2xx', function () {
    $result = makeResult(200, ['message_id' => '1']);

    expect($result->succeeded())->toBeTrue()
        ->and($result->failed())->toBeFalse()
        ->and($result->status())->toBe(200)
        ->and($result->data())->toBe(['message_id' => '1'])
        ->and($result->errors())->toBe([])
        ->and($result->rateLimit())->toBeNull();
});

it('reports failed for 4xx', function () {
    $result = makeResult(400, ['errors' => ['body is required']]);

    expect($result->succeeded())->toBeFalse()
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400)
        ->and($result->errors())->toBe(['body is required']);
});

it('reports failed for 5xx', function () {
    $result = makeResult(503, ['errors' => ['service unavailable']]);

    expect($result->failed())->toBeTrue();
});

it('exposes rateLimit array when x-ratelimit headers present', function () {
    $result = makeResult(
        429,
        ['errors' => ['rate limit exceeded']],
        [
            'x-ratelimit-limit' => '200',
            'x-ratelimit-remaining' => '0',
            'x-ratelimit-reset' => '1735718400',
        ],
    );

    expect($result->rateLimit())->toBe([
        'limit' => 200,
        'remaining' => 0,
        'reset' => 1735718400,
    ]);
});

it('builds a ChatworkRequestException via toException', function () {
    $result = makeResult(400, ['errors' => ['body is required']]);

    $exception = $result->toException();

    expect($exception)->toBeInstanceOf(ChatworkRequestException::class)
        ->and($exception->status())->toBe(400)
        ->and($exception->method())->toBe('GET')
        ->and($exception->path())->toBe('/probe')
        ->and($exception->operationId())->toBe('probe')
        ->and($exception->errors())->toBe(['body is required']);
});
