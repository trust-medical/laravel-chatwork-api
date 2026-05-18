<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

/**
 * タスク期限の粒度。
 *
 * バッキング値はタスクの `limit_type` フィールドに対応する。
 * `none`（期限なし）、`date`（日付のみ）、`time`（日時）の3種類。
 */
enum LimitType: string
{
    case None = 'none';
    case Date = 'date';
    case Time = 'time';
}
