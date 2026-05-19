<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\ValidatesBodyLength;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class UpdateMessageRequest
{
    use ValidatesBodyLength;

    /**
     * @throws ChatworkValidationException 本文が空、または Chatwork の文字数上限を超えた場合。
     */
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
        self::assertBodyLength(
            $this->body,
            MessageBodyConstraints::BODY_MIN,
            MessageBodyConstraints::BODY_MAX,
            'Message',
        );
    }
}
