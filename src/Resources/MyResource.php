<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/my/*` エンドポイントグループ（status, tasks）の公開 API。
 */
final class MyResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * 認証ユーザーの未読・メンション・タスク件数を取得する (GET /my/status)。
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
    public function status(): mixed
    {
        return $this->client->send('GET', '/my/status', [], MyStatusData::class, 'getMyStatus');
    }

    /**
     * 認証ユーザーに割り当てられたタスク一覧を取得する（任意フィルタ対応）(GET /my/tasks)。
     *
     * @return list<MyTaskData> デフォルト Dto モード時（204 は `[]` に縮退）；他の ResponseMode はそれぞれの型を返す
     *
     * @throws ChatworkRequestException throw するモード（asArray/asDto/asCollection）での 4xx/5xx 時。
     */
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

        // ResponseMode::Dto はパッケージのデフォルトだが、ワイヤー形状が
        // 配列のため、内部的に Collection モード経由でルーティングして
        // アンラップする。このパスにより仕様上の 204 空ボディも [] に縮退する。
        // 他のモードは ChatworkClient::send をそのまま通す。
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
