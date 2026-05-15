<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\Response;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

final class ResponseMapper
{
    /**
     * @param  class-string|null  $dtoClass
     */
    public function map(
        Response $response,
        ResponseMode $mode,
        ?string $dtoClass = null,
        ?string $method = null,
        ?string $path = null,
        ?string $operationId = null,
    ): mixed {
        return match ($mode) {
            ResponseMode::Array => $this->toArrayOrThrow($response, $method, $path, $operationId),
            ResponseMode::Response => $response,
            ResponseMode::PsrResponse => $response->toPsrResponse(),
            ResponseMode::Result => new Result($response, $method, $path, $operationId),
            ResponseMode::Dto, ResponseMode::Collection => throw new \LogicException(
                sprintf('DTO mapping is added in Phase 2 (mode=%s, dto=%s)', $mode->value, $dtoClass ?? 'null'),
            ),
        };
    }

    /**
     * @return array<int|string, mixed>
     */
    private function toArrayOrThrow(
        Response $response,
        ?string $method,
        ?string $path,
        ?string $operationId,
    ): array {
        if ($response->failed()) {
            throw ChatworkRequestException::fromResponse(
                $response,
                $method ?? 'UNKNOWN',
                $path ?? '',
                $operationId,
            );
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }
}
