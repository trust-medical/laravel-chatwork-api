<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class ContactsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        $path = '/contacts';

        // GET /contacts returns array<Contact> on 200 and an empty body on
        // 204 (the only list endpoint whose spec declares 204). Routing Dto
        // mode through Collection makes both degrade correctly: 204 -> [].
        // Other modes flow through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                [],
                ContactData::class,
                'listContacts',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, [], ContactData::class, 'listContacts');
    }
}
