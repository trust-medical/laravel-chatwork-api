<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class DeletedMessage
{
    public function __construct(public string $messageId) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((string) ($data['message_id'] ?? ''));
    }
}
