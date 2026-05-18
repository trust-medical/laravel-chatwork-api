<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response as IlluminateResponse;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Http\Result;

/**
 * @param  array<int|string, mixed>|string  $body
 */
function fakeChatworkResponse(int $status, array|string $body = []): IlluminateResponse
{
    Http::fake([
        'https://api.chatwork.com/v2/probe' => Http::response($body, $status),
    ]);

    return Http::get('https://api.chatwork.com/v2/probe');
}

it('Array モードをデコード済みの JSON 配列にルーティングする', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(200, ['message_id' => '5']);

    expect($mapper->map($response, ResponseMode::Array, null, 'GET', '/probe', 'probe'))
        ->toBe(['message_id' => '5']);
});

it('Response モードを Illuminate Response にそのままルーティングする', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(200, ['ok' => true]);

    expect($mapper->map($response, ResponseMode::Response, null, 'GET', '/probe', 'probe'))
        ->toBe($response);
});

it('PsrResponse モードを PSR-7 ResponseInterface にルーティングする', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(200, ['ok' => true]);

    $psr = $mapper->map($response, ResponseMode::PsrResponse, null, 'GET', '/probe', 'probe');
    /** @var ResponseInterface $psr */
    expect($psr)->toBeInstanceOf(ResponseInterface::class)
        ->and($psr->getStatusCode())->toBe(200);
});

it('Result モードは 4xx でも Result 値オブジェクトにルーティングする', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(400, ['errors' => ['nope']]);

    $result = $mapper->map($response, ResponseMode::Result, null, 'POST', '/probe', 'probe');
    /** @var Result $result */
    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->failed())->toBeTrue()
        ->and($result->status())->toBe(400);
});

it('Array モードで 4xx の場合は ChatworkRequestException をスローする', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(400, ['errors' => ['nope']]);

    $mapper->map($response, ResponseMode::Array, null, 'POST', '/probe', 'probe');
})->throws(ChatworkRequestException::class);

it('Response モードで 5xx の場合はスローしない', function () {
    $mapper = new ResponseMapper();
    $response = fakeChatworkResponse(500, 'Internal Server Error');

    $result = $mapper->map($response, ResponseMode::Response, null, 'GET', '/probe', 'probe');

    expect($result)->toBe($response);
});
