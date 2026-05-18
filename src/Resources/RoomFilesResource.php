<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomFileData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;

final class RoomFilesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    public function list(int $roomId, ?int $accountId = null): mixed
    {
        $query = $accountId !== null ? ['account_id' => $accountId] : [];
        $path = sprintf('/rooms/%d/files', $roomId);

        // ResponseMode::Dto is the package default but the wire shape here is an
        // array of files, so internally route through Collection mode and
        // unwrap. Other modes (Collection / Array / Response / PsrResponse /
        // Result) flow straight through ChatworkClient::send unchanged.
        if ($this->client->mode() === ResponseMode::Dto) {
            $collection = $this->client->withMode(ResponseMode::Collection)->send(
                'GET',
                $path,
                $query,
                RoomFileData::class,
                'listRoomFiles',
            );

            return $collection->all();
        }

        return $this->client->send('GET', $path, $query, RoomFileData::class, 'listRoomFiles');
    }

    public function upload(int $roomId, UploadRoomFileRequest $request): mixed
    {
        return $this->client->upload(
            sprintf('/rooms/%d/files', $roomId),
            'file',
            $request->contents(),
            $request->filename(),
            $request->toFields(),
            UploadedRoomFile::class,
            'uploadRoomFile',
        );
    }

    public function find(int $roomId, int $fileId, ?bool $createDownloadUrl = null): mixed
    {
        $query = $createDownloadUrl !== null
            ? ['create_download_url' => $createDownloadUrl ? 1 : 0]
            : [];

        return $this->client->send(
            'GET',
            sprintf('/rooms/%d/files/%d', $roomId, $fileId),
            $query,
            RoomFileData::class,
            'getRoomFile',
        );
    }
}
