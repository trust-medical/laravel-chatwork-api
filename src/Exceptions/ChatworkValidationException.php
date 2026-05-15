<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;

class ChatworkValidationException extends RuntimeException
{
    /**
     * @return array<string, array<int, string>>
     */
    public function violations(): array
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
