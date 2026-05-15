<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class NoContentData
{
    public function __construct() {}

    /**
     * @param  array<string, mixed>  $_data  Ignored: 204 No Content has no payload, but the
     *                                       signature mirrors other Response DTOs so
     *                                       ResponseMapper can hydrate uniformly.
     */
    public static function fromArray(array $_data): self
    {
        return new self();
    }
}
