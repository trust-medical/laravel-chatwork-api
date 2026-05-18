<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\ContactData;
use TrustMedical\LaravelChatworkApi\Data\Responses\IncomingRequestData;
use TrustMedical\LaravelChatworkApi\Data\Responses\NoContentData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class IncomingRequestsResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(): mixed
    {
        $path = '/incoming_requests';

        // ResponseMode::Dto is the package default but the wire shape here
        // is an array, so internally route through Collection mode and
        // unwrap. This path also makes the spec's 204 empty body degrade
        // to []. Other modes flow through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                [],
                IncomingRequestData::class,
                'listIncomingRequests',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, [], IncomingRequestData::class, 'listIncomingRequests');
    }

    public function accept(int $requestId): mixed
    {
        // The spec declares no request body, so the payload is empty. The
        // 200 response is the now-contact account, byte-identical to
        // ContactData, which is why ContactData is reused here.
        return $this->client->send(
            'PUT',
            sprintf('/incoming_requests/%d', $requestId),
            [],
            ContactData::class,
            'acceptIncomingRequest',
        );
    }

    public function decline(int $requestId): mixed
    {
        // The spec returns 204 No Content with no body, mapped to
        // NoContentData.
        return $this->client->send(
            'DELETE',
            sprintf('/incoming_requests/%d', $requestId),
            [],
            NoContentData::class,
            'declineIncomingRequest',
        );
    }
}
