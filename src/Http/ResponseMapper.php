<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
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
            ResponseMode::Dto => $this->toDto($response, $dtoClass, $method, $path, $operationId),
            ResponseMode::Collection => $this->toCollection($response, $dtoClass, $method, $path, $operationId),
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
        $this->throwIfFailed($response, $method, $path, $operationId);

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  class-string|null  $dtoClass
     */
    private function toDto(
        Response $response,
        ?string $dtoClass,
        ?string $method,
        ?string $path,
        ?string $operationId,
    ): object {
        $this->throwIfFailed($response, $method, $path, $operationId);

        if ($dtoClass === null) {
            throw new \LogicException('dtoClass is required for Dto response mode.');
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            $decoded = [];
        }

        /** @var object $instance */
        $instance = $dtoClass::fromArray($decoded);

        return $instance;
    }

    /**
     * @param  class-string|null  $dtoClass
     * @return Collection<int, object>
     */
    private function toCollection(
        Response $response,
        ?string $dtoClass,
        ?string $method,
        ?string $path,
        ?string $operationId,
    ): Collection {
        $this->throwIfFailed($response, $method, $path, $operationId);

        if ($dtoClass === null) {
            throw new \LogicException('dtoClass is required for Collection response mode.');
        }

        $decoded = $response->json();
        if (! is_array($decoded)) {
            $decoded = [];
        }

        /** @var Collection<int, object> $collection */
        $collection = Collection::make($decoded)->values()->map(
            static fn (mixed $item): object => $dtoClass::fromArray(is_array($item) ? $item : []),
        );

        return $collection;
    }

    private function throwIfFailed(
        Response $response,
        ?string $method,
        ?string $path,
        ?string $operationId,
    ): void {
        if ($response->failed()) {
            throw ChatworkRequestException::fromResponse(
                $response,
                $method ?? 'UNKNOWN',
                $path ?? '',
                $operationId,
            );
        }
    }
}
