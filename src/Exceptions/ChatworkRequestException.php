<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;

class ChatworkRequestException extends RuntimeException
{
    public function status(): int
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function method(): string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function path(): string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function operationId(): ?string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function body(): string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function error(): ?string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function errorDescription(): ?string
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    /**
     * @return array{limit: int, remaining: int, reset: int}|null
     */
    public function rateLimit(): ?array
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
