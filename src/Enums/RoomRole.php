<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

/**
 * ルーム内のメンバーロール。
 *
 * バッキング値は Chatwork のメンバーロールフィールド
 * （`members_admin_ids` / `members_member_ids` / `members_readonly_ids`）に対応する。
 */
enum RoomRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Readonly = 'readonly';
}
