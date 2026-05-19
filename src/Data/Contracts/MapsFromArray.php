<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Contracts;

use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;

/**
 * 連想配列（API レスポンス / 永続化スナップショット）から自身を生成する
 * Response DTO のファクトリ契約。
 *
 * {@see ResponseMapper} はこの契約を
 * 満たすクラスのみ hydrate でき、`class-string<MapsFromArray>` により
 * 非対応クラスの誤渡しを PHPStan が静的に検出できる。
 *
 * @api 安定したファクトリ契約。シグネチャは v1 で恒久固定される後方互換契約である。
 */
interface MapsFromArray
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static;
}
