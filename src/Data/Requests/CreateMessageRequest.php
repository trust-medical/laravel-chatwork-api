<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\ValidatesBodyLength;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class CreateMessageRequest
{
    use ValidatesBodyLength;

    /**
     * @throws ChatworkValidationException 本文が空、または Chatwork の文字数上限を超えた場合。
     */
    public function __construct(
        public string $body,
        public ?bool $selfUnread = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{body: string, self_unread?: 0|1}
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
        self::assertBodyLength(
            $this->body,
            MessageBodyConstraints::BODY_MIN,
            MessageBodyConstraints::BODY_MAX,
            'Message',
        );
    }
}
