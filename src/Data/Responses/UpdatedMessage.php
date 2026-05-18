<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class UpdatedMessage implements MapsFromArray
{
    public function __construct(public string $messageId) {}

    /**
     * @param  array{message_id?: int|string}  $data
     */
    public static function fromArray(array $data): static
    {
        return new self((string) ($data['message_id'] ?? ''));
    }
}
