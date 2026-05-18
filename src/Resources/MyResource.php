<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

final class MyResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function status(): mixed
    {
        return $this->client->send('GET', '/my/status', [], MyStatusData::class, 'getMyStatus');
    }

    public function tasks(?int $assignedByAccountId = null, ?TaskStatus $status = null): mixed
    {
        $query = [];
        if ($assignedByAccountId !== null) {
            $query['assigned_by_account_id'] = $assignedByAccountId;
        }
        if ($status !== null) {
            $query['status'] = $status->value;
        }

        $path = '/my/tasks';

        // ResponseMode::Dto is the package default but the wire shape here
        // is an array, so internally route through Collection mode and
        // unwrap. This path also makes the spec's 204 empty body degrade
        // to []. Other modes flow through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                $query,
                MyTaskData::class,
                'listMyTasks',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, $query, MyTaskData::class, 'listMyTasks');
    }
}
