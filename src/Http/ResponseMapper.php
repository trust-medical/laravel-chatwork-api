<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

final class ResponseMapper
{
    /**
     * HTTP レスポンスを ResponseMode が指示する値に変換する。
     *
     * 具体的な戻り値の型は $mode に完全に依存する:
     *  - Array      => array<int|string, mixed> (4xx/5xx で throw)
     *  - Dto        => $dtoClass のオブジェクトインスタンス (4xx/5xx で throw)
     *  - Collection => $dtoClass の Collection<int, object> (4xx/5xx で throw)
     *  - Response   => Illuminate\Http\Client\Response (throw しない)
     *  - PsrResponse=> Psr\Http\Message\ResponseInterface (throw しない)
     *  - Result     => 成功/失敗をラップした Result (throw しない)
     *
     * @param  class-string|null  $dtoClass  Dto / Collection モードでのみ必須
     * @return array<int|string, mixed>|object|Collection<int, object>|Response|ResponseInterface|Result
     *
     * @throws ChatworkRequestException Array / Dto / Collection モードで 4xx/5xx の場合
     * @throws \LogicException Dto / Collection モードで $dtoClass が未指定の場合
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
     *
     * @throws ChatworkRequestException 4xx/5xx の場合
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
     *
     * @throws ChatworkRequestException 4xx/5xx の場合
     * @throws \LogicException $dtoClass が null の場合
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
     *
     * @throws ChatworkRequestException 4xx/5xx の場合
     * @throws \LogicException $dtoClass が null の場合
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

    /**
     * @throws ChatworkRequestException レスポンスステータスが 4xx/5xx の場合
     */
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
