<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyStatusData;
use TrustMedical\LaravelChatworkApi\Data\Responses\MyTaskData;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * Chatwork `/my/*` エンドポイントグループ（status, tasks）の公開 API。
 *
 * @api 公開 API。宣言戻り値型は asDto() モード契約（他モードは実行時型が変わる。型対応表は README / docs を参照）。
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
     * @return list<MyTaskData> asDto() 契約（204 は `[]` に縮退）。他モードは ResponseMode に従い実行時型が変わる
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

        return $this->client->sendList('GET', '/my/tasks', $query, MyTaskData::class, 'listMyTasks');
    }
}
