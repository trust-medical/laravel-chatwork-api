<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class MarkAsUnreadRequest
{
    public function __construct(public string $messageId)
    {
        $this->validate();
    }

    /**
     * @return array{message_id: string}
     */
    public function toArray(): array
    {
        return ['message_id' => $this->messageId];
    }

    private function validate(): void
    {
        if ($this->messageId === '') {
            throw new ChatworkValidationException(
                'message_id is required when marking a message as unread.',
                ['message_id' => ['must not be empty']],
            );
        }
    }
}
