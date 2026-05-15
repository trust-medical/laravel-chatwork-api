<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class CreateMessageRequest
{
    public function __construct(
        public string $body,
        public ?bool $selfUnread = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{body: string, self_unread?: int}
     */
    public function toArray(): array
    {
        $payload = ['body' => $this->body];

        if ($this->selfUnread !== null) {
            $payload['self_unread'] = $this->selfUnread ? 1 : 0;
        }

        return $payload;
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
