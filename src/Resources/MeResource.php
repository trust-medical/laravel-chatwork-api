<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyAccountData;

final class MeResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function get(): mixed
    {
        return $this->client->send('GET', '/me', [], MyAccountData::class, 'getMe');
    }
}
