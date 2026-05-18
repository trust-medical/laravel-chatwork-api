<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\RoomFileData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UploadedRoomFile;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Chatwork `/rooms/{room_id}/files` エンドポイントグループ（list / find / upload）の公開 API。
 */
final class RoomFilesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * ルームにアップロードされたファイル一覧を取得する（アップローダーでフィルタ可能）
     * (GET /rooms/{room_id}/files)。
     *
     * @return list<RoomFileData> デフォルト Dto モード時；他の ResponseMode はそれぞれの型を返す
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function list(int $roomId, ?int $accountId = null): mixed
    {
        $query = $accountId !== null ? ['account_id' => $accountId] : [];
        $path = sprintf('/rooms/%d/files', $roomId);

        // ResponseMode::Dto はパッケージのデフォルトだが、ワイヤー形状がファイルの
        // 配列のため、内部的に Collection モード経由でルーティングしてアンラップする。
        // 他のモード（Collection / Array / Response / PsrResponse / Result）は
        // ChatworkClient::send をそのまま通す。
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

    /**
     * multipart 形式でルームにファイルをアップロードする (POST /rooms/{room_id}/files)。
     *
     * @throws ChatworkValidationException リクエストのファイルが読み取り不能・無効、またはメッセージが無効な場合（送信前、UploadRoomFileRequest が送出）。
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
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

    /**
     * 指定ルームの単一ファイルを取得する（一時ダウンロード URL の発行も可能）
     * (GET /rooms/{room_id}/files/{file_id})。
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
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
