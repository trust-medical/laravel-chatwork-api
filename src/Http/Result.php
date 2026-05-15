<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\Response;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

final class Result
{
    public function __construct(
        private readonly Response $response,
        private readonly ?string $method = null,
        private readonly ?string $path = null,
        private readonly ?string $operationId = null,
    ) {}

    public function succeeded(): bool
    {
        return $this->response->successful();
    }

    public function failed(): bool
    {
        return $this->response->failed();
    }

    public function status(): int
    {
        return $this->response->status();
    }

    public function data(): mixed
    {
        return $this->response->json();
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        $decoded = $this->response->json();
        if (! is_array($decoded)) {
            return [];
        }

        $errors = $decoded['errors'] ?? null;
        if (! is_array($errors)) {
            return [];
        }

        return array_values(array_filter($errors, static fn (mixed $v): bool => is_string($v)));
    }

    /**
     * @return array{limit: int, remaining: int, reset: int}|null
     */
    public function rateLimit(): ?array
    {
        $limit = $this->response->header('x-ratelimit-limit');
        if ($limit === '') {
            return null;
        }

        return [
            'limit' => (int) $limit,
            'remaining' => (int) $this->response->header('x-ratelimit-remaining'),
            'reset' => (int) $this->response->header('x-ratelimit-reset'),
        ];
    }

    public function toException(): ChatworkRequestException
    {
        return ChatworkRequestException::fromResponse(
            $this->response,
            $this->method ?? 'UNKNOWN',
            $this->path ?? '',
            $this->operationId,
        );
    }
}
