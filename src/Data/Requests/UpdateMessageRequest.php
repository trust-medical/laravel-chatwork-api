<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class UpdateMessageRequest
{
    public function __construct(public string $body)
    {
        $this->validate();
    }

    /**
     * @return array{body: string}
     */
    public function toArray(): array
    {
        return ['body' => $this->body];
    }

    private function validate(): void
    {
        $length = mb_strlen($this->body);

        if ($length < MessageBodyConstraints::BODY_MIN) {
            throw new ChatworkValidationException(
                'Message body must not be empty.',
                ['body' => ['must not be empty']],
            );
        }

        if ($length > MessageBodyConstraints::BODY_MAX) {
            throw new ChatworkValidationException(
                sprintf('Message body must be %d characters or less.', MessageBodyConstraints::BODY_MAX),
                ['body' => [sprintf('must be %d characters or less', MessageBodyConstraints::BODY_MAX)]],
            );
        }
    }
}
