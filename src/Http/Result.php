<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

final class Result
{
    public function succeeded(): bool
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function failed(): bool
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function status(): ?int
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function data(): mixed
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

    /**
     * @return array{limit: int, remaining: int, reset: int}|null
     */
    public function rateLimit(): ?array
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function toException(): ChatworkRequestException
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
