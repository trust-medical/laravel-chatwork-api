<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Connection;

final class ChatworkPendingRequestFactory
{
    /**
     * 指定された connection に対応する設定済み Laravel HTTP クライアントを構築する。
     *
     * ベース URI・JSON Accept ヘッダー・パッケージ User-Agent・タイムアウトを適用し、
     * 認証ヘッダーの注入は connection の credentials（API token と Bearer token は排他）に委譲する。
     */
    public function create(Connection $connection): PendingRequest
    {
        $pending = Http::baseUrl($connection->baseUri)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => UserAgent::string(),
            ])
            ->timeout($connection->timeout);

        return $connection->credentials->applyTo($pending);
    }
}
