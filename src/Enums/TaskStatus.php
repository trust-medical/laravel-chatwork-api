<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

/**
 * タスクの完了状態。
 *
 * バッキング値はタスクの `status` フィールドに対応し、
 * タスク一覧のフィルタリングとタスクのステータス更新の両方で使用する。
 */
enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
}
