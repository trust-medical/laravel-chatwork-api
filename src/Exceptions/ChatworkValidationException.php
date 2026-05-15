<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;

class ChatworkValidationException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $violations
     */
    public function __construct(
        string $message,
        private readonly array $violations = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
