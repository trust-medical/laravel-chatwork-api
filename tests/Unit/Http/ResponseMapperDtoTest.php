<?php

declare(strict_types=1);

use Illuminate\Http\Client\Response as IlluminateResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;

/**
 * @param  array<int|string, mixed>|string  $body
 */
function fakeDtoResponse(int $status, array|string $body = []): IlluminateResponse
{
    Http::fake([
        'https://api.chatwork.com/v2/dto-probe' => Http::response($body, $status),
    ]);

    return Http::get('https://api.chatwork.com/v2/dto-probe');
}

it('Dto mode maps to a single DTO via fromArray', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(201, ['message_id' => '1024']);

    $dto = $mapper->map($response, ResponseMode::Dto, CreatedMessage::class, 'POST', '/dto-probe', 'probe');

    expect($dto)->toBeInstanceOf(CreatedMessage::class)
        ->and($dto->messageId)->toBe('1024');
});

it('Dto mode throws ChatworkRequestException on 4xx', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(400, ['errors' => ['nope']]);

    $mapper->map($response, ResponseMode::Dto, CreatedMessage::class, 'POST', '/dto-probe', 'probe');
})->throws(ChatworkRequestException::class);

it('Dto mode requires a dtoClass and throws LogicException without it', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(200, ['message_id' => '1']);

    $mapper->map($response, ResponseMode::Dto, null, 'POST', '/dto-probe', 'probe');
})->throws(LogicException::class, 'dtoClass is required');

it('Collection mode maps each item via fromArray and returns a Collection', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(200, [
        ['message_id' => '1'],
        ['message_id' => '2'],
        ['message_id' => '3'],
    ]);

    $collection = $mapper->map($response, ResponseMode::Collection, CreatedMessage::class, 'GET', '/dto-probe', 'probe');

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection)->toHaveCount(3);

    /** @var Collection<int, CreatedMessage> $collection */
    expect($collection->map(fn (CreatedMessage $m): string => $m->messageId)->all())
        ->toBe(['1', '2', '3']);
});

it('Collection mode throws ChatworkRequestException on 4xx', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(401, ['errors' => ['Invalid API token']]);

    $mapper->map($response, ResponseMode::Collection, CreatedMessage::class, 'GET', '/dto-probe', 'probe');
})->throws(ChatworkRequestException::class);

it('Collection mode returns an empty Collection for an empty JSON array', function () {
    $mapper = new ResponseMapper();
    $response = fakeDtoResponse(200, []);

    $collection = $mapper->map($response, ResponseMode::Collection, CreatedMessage::class, 'GET', '/dto-probe', 'probe');

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection)->toHaveCount(0);
});
